<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Services\AttemptFinalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExamController extends Controller
{
    private function registration(Request $request)
    {
        $participant = $request->user()->participant()->firstOrFail();

        return DB::table('event_registrations')->where('participant_id', $participant->id)->latest('id')->firstOrFail();
    }

    public function start(Request $request, int $session)
    {
        $registration = $this->registration($request);
        $examSession = DB::table('exam_sessions as es')
            ->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')
            ->join('events as e', 'e.id', '=', 'ep.event_id')
            ->where('es.id', $session)
            ->select('es.*', 'ep.duration_minutes', 'ep.id as package_id', 'ep.event_id', 'ep.version as package_version', 'ep.subject_id', 'ep.status as package_status', 'ep.randomize_questions', 'ep.randomize_options', 'e.status as event_status', 'e.event_start_at', 'e.event_end_at', 'e.settings as event_settings')
            ->firstOrFail();

        abort_unless($registration->event_id === $examSession->event_id, 403, 'Sesi bukan bagian dari event peserta.');
        abort_unless(in_array($registration->status, ['verified', 'eligible'], true), 403, 'Pendaftaran belum eligible.');
        abort_unless($examSession->event_status === 'published' && $examSession->package_status === 'published' && $examSession->status === 'open', 403, 'Sesi ujian belum aktif.');
        abort_if(now()->lt($examSession->starts_at), 403, 'Sesi ujian belum dimulai.');
        abort_if(now()->gt($examSession->ends_at), 403, 'Sesi ujian telah berakhir.');
        abort_unless(DB::table('participant_subjects')->where('registration_id', $registration->id)->where('subject_id', $examSession->subject_id)->exists(), 403, 'Mata uji belum dipilih.');

        $eventSettings = json_decode($examSession->event_settings ?: '{}', true);
        $attempt = DB::transaction(function () use ($request, $registration, $examSession, $eventSettings) {
            $existing = ExamAttempt::query()->where('registration_id', $registration->id)->where('exam_session_id', $examSession->id)->lockForUpdate()->first();
            if ($existing) {
                $fingerprint = $request->string('device_fingerprint')->limit(255)->value() ?: hash('sha256', (string) $request->userAgent());
                if ($existing->status === 'in_progress' && $existing->device_fingerprint && ! hash_equals($existing->device_fingerprint, $fingerprint)) {
                    DB::table('integrity_events')->insert(['attempt_id' => $existing->id, 'event_type' => 'new_device', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'metadata' => json_encode(['fingerprint' => $fingerprint]), 'created_at' => now()]);
                }

                return $existing;
            }
            $activeAttempts = ExamAttempt::query()->where('registration_id', $registration->id)->where('status', 'in_progress')->lockForUpdate()->get(['id'])->count();
            abort_if($activeAttempts >= (int) ($eventSettings['max_concurrent_sessions'] ?? 1), 409, 'Batas sesi ujian aktif pada akun ini telah tercapai.');

            $expiresAt = now()->addMinutes($examSession->duration_minutes);
            if ($expiresAt->gt($examSession->ends_at)) {
                $expiresAt = Carbon::parse($examSession->ends_at);
            }
            $attempt = ExamAttempt::create([
                'registration_id' => $registration->id,
                'exam_session_id' => $examSession->id,
                'package_version' => $examSession->package_version,
                'scoring_formula_id' => DB::table('scoring_formulas')->where('event_id', $registration->event_id)->where('status', 'active')->latest('version')->value('id'),
                'status' => 'in_progress',
                'started_at' => now(),
                'expires_at' => $expiresAt,
                'device_fingerprint' => $request->string('device_fingerprint')->limit(255)->value() ?: hash('sha256', (string) $request->userAgent()),
                'ip_history' => [$request->ip()],
                'last_activity_at' => now(),
                'score_status' => 'pending',
            ]);

            $questionIds = DB::table('exam_package_questions')->where('exam_package_id', $examSession->package_id)->orderBy('position')->pluck('question_id');
            if ($examSession->randomize_questions) {
                $questionIds = $questionIds->shuffle();
            }
            foreach ($questionIds as $index => $questionId) {
                $question = DB::table('questions as q')->join('subjects as s', 's.id', '=', 'q.subject_id')->where('q.id', $questionId)->select('q.id', 'q.code', 'q.explanation', 'q.type', 'q.content', 'q.matrix_rows', 'q.image_path', 'q.domain', 'q.weight', 's.name as subject')->firstOrFail();
                $options = DB::table('question_options')->where('question_id', $questionId)->orderBy('position')->get(['id', 'label', 'content', 'is_correct']);
                $optionIds = $options->pluck('id');
                if ($examSession->randomize_options) {
                    $optionIds = $optionIds->shuffle();
                }
                AttemptQuestion::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                    'question_snapshot' => [
                        'code' => $question->code,
                        'explanation' => $question->explanation,
                        'type' => $question->type,
                        'content' => $question->content,
                        'matrix_rows' => json_decode($question->matrix_rows ?: '[]', true),
                        'image_path' => $question->image_path,
                        'domain' => $question->domain,
                        'weight' => (float) $question->weight,
                        'subject' => $question->subject,
                        'options' => $options->map(fn ($option) => ['id' => $option->id, 'label' => $option->label, 'content' => $option->content, 'is_correct' => (bool) $option->is_correct])->all(),
                    ],
                    'position' => $index + 1,
                    'option_order' => $optionIds->values()->all(),
                ]);
            }

            return $attempt;
        });

        return $attempt->status === 'in_progress'
            ? redirect()->route('exam.show', $attempt)
            : redirect()->route('results');
    }

    public function show(Request $request, ExamAttempt $attempt, AttemptFinalizer $finalizer)
    {
        $registration = $this->registration($request);
        abort_unless($attempt->registration_id === $registration->id, 403);
        if ($attempt->status === 'in_progress' && now()->gte($attempt->expires_at)) {
            $attempt = $finalizer->finalize($attempt, 'auto');
        }

        $attempt->load(['questions.question.subject', 'questions.question.options', 'questions.answer']);
        $questions = $attempt->questions->map(function ($snapshot) {
            $frozen = $snapshot->question_snapshot;
            $options = collect($frozen['options'] ?? $snapshot->question->options->toArray())->sortBy(fn ($option) => array_search($option['id'], $snapshot->option_order ?? [], true))->values()->map(fn ($option) => collect($option)->only(['id', 'label', 'content']));

            return [
                'id' => $snapshot->id,
                'position' => $snapshot->position,
                'type' => $frozen['type'] ?? $snapshot->question->type,
                'content' => $frozen['content'] ?? $snapshot->question->content,
                'matrix_rows' => collect($frozen['matrix_rows'] ?? $snapshot->question->matrix_rows ?? [])->map(fn ($row) => ['statement' => $row['statement']])->values(),
                'image_path' => $frozen['image_path'] ?? $snapshot->question->image_path,
                'domain' => $frozen['domain'] ?? $snapshot->question->domain,
                'subject' => $frozen['subject'] ?? $snapshot->question->subject->name,
                'options' => $options,
                'answer_ids' => $snapshot->answer?->selected_option_ids ?: ($snapshot->answer?->question_option_id ? [$snapshot->answer->question_option_id] : []),
                'matrix_answer_ids' => $snapshot->answer?->matrix_answers ?? (object) [],
                'is_flagged' => $snapshot->is_flagged,
            ];
        });

        return Inertia::render('Exam/Runner', [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'expires_at' => $attempt->expires_at?->toIso8601String(),
                'server_time' => now()->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ],
            'questions' => $questions,
            'fullscreenRequired' => (bool) (json_decode((string) DB::table('exam_attempts as a')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->join('events as e', 'e.id', '=', 'r.event_id')->where('a.id', $attempt->id)->value('e.settings'), true)['fullscreen_required'] ?? false),
        ]);
    }

    public function answer(Request $request, ExamAttempt $attempt, AttemptQuestion $question, AttemptFinalizer $finalizer)
    {
        $registration = $this->registration($request);
        abort_unless($attempt->registration_id === $registration->id && $question->attempt_id === $attempt->id, 403);
        if ($attempt->status === 'in_progress' && now()->gte($attempt->expires_at)) {
            $finalizer->finalize($attempt, 'auto');
            abort(409, 'Waktu ujian telah berakhir.');
        }
        abort_if($attempt->status !== 'in_progress', 409, 'Ujian sudah dikunci.');

        $data = $request->validate([
            'option_id' => ['nullable', 'integer'],
            'option_ids' => ['nullable', 'array', 'min:1'],
            'option_ids.*' => ['integer'],
            'matrix_answers' => ['nullable', 'array'],
            'matrix_answers.*' => ['integer'],
            'idempotency_key' => ['required', 'string', 'max:80'],
        ]);
        $questionModel = $question->question()->with('options')->firstOrFail();
        $frozen = $question->question_snapshot;
        $type = $frozen['type'] ?? $questionModel->type;
        $matrixAnswers = collect($data['matrix_answers'] ?? [])->map(fn ($id) => (int) $id);
        $selectedIds = ($type === 'matrix' ? $matrixAnswers->values() : collect($data['option_ids'] ?? (isset($data['option_id']) ? [$data['option_id']] : [])))->map(fn ($id) => (int) $id)->unique()->sort()->values();
        if ($selectedIds->isEmpty()) {
            throw ValidationException::withMessages(['option_ids' => 'Pilih minimal satu jawaban.']);
        }
        if (! in_array($type, ['multiple_choice', 'matrix'], true) && $selectedIds->count() !== 1) {
            throw ValidationException::withMessages(['option_ids' => 'Soal ini hanya menerima satu jawaban.']);
        }
        $validOptionIds = collect($frozen['options'] ?? $questionModel->options->toArray())->pluck('id')->map(fn ($id) => (int) $id);
        if ($selectedIds->diff($validOptionIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['option_ids' => 'Opsi jawaban tidak valid untuk soal ini.']);
        }
        if ($type === 'matrix' && $matrixAnswers->keys()->contains(fn ($index) => ! array_key_exists((int) $index, $frozen['matrix_rows'] ?? $questionModel->matrix_rows ?? []))) {
            throw ValidationException::withMessages(['matrix_answers' => 'Baris matriks tidak valid.']);
        }

        $answer = DB::transaction(function () use ($question, $data, $selectedIds, $matrixAnswers, $type) {
            AttemptQuestion::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $duplicate = DB::table('answer_mutations')->where('idempotency_key', $data['idempotency_key'])->first();
            if ($duplicate) {
                return Answer::where('attempt_question_id', $question->id)->firstOrFail();
            }

            $current = Answer::where('attempt_question_id', $question->id)->first();
            $revision = ($current?->revision ?? 0) + 1;
            $answer = Answer::updateOrCreate(['attempt_question_id' => $question->id], [
                'question_option_id' => $selectedIds->count() === 1 ? $selectedIds->first() : null,
                'selected_option_ids' => $selectedIds->all(),
                'matrix_answers' => $type === 'matrix' ? $matrixAnswers->all() : null,
                'idempotency_key' => $data['idempotency_key'],
                'revision' => $revision,
                'answered_at' => now(),
            ]);
            DB::table('answer_mutations')->insert([
                'attempt_question_id' => $question->id,
                'idempotency_key' => $data['idempotency_key'],
                'revision' => $revision,
                'selected_option_ids' => json_encode($selectedIds->all()),
                'created_at' => now(),
            ]);

            return $answer;
        });

        $attempt->update(['last_activity_at' => now(), 'last_autosave_at' => now()]);
        Cache::put('attempt:'.$attempt->id.':heartbeat', now()->timestamp, 600);

        return response()->json(['saved' => true, 'revision' => $answer->revision, 'selected_option_ids' => $answer->selected_option_ids, 'saved_at' => $answer->answered_at->toIso8601String()]);
    }

    public function flag(Request $request, ExamAttempt $attempt, AttemptQuestion $question)
    {
        $registration = $this->registration($request);
        abort_unless($attempt->registration_id === $registration->id && $question->attempt_id === $attempt->id, 403);
        abort_if($attempt->status !== 'in_progress' || now()->gte($attempt->expires_at), 409, 'Ujian sudah dikunci.');
        $question->update(['is_flagged' => ! $question->is_flagged]);
        $attempt->update(['last_activity_at' => now()]);

        return response()->json(['flagged' => $question->fresh()->is_flagged]);
    }

    public function integrity(Request $request, ExamAttempt $attempt)
    {
        $registration = $this->registration($request);
        abort_unless($attempt->registration_id === $registration->id, 403);
        $data = $request->validate(['type' => ['required', 'in:tab_hidden,fullscreen_exit,reconnect,new_device'], 'metadata' => ['nullable', 'array']]);
        DB::table('integrity_events')->insert([
            'attempt_id' => $attempt->id,
            'event_type' => $data['type'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => json_encode($data['metadata'] ?? []),
            'created_at' => now(),
        ]);
        if ($data['type'] === 'tab_hidden') {
            $attempt->increment('tab_switch_count');
        }
        $ips = collect($attempt->ip_history ?? [])->push($request->ip())->filter()->unique()->values()->all();
        $attempt->update(['last_activity_at' => now(), 'ip_history' => $ips]);

        return response()->json(['recorded' => true]);
    }

    public function heartbeat(Request $request, ExamAttempt $attempt)
    {
        $registration = $this->registration($request);
        abort_unless($attempt->registration_id === $registration->id, 403);
        if ($attempt->status === 'in_progress' && now()->gte($attempt->expires_at)) {
            app(AttemptFinalizer::class)->finalize($attempt, 'auto');
        } else {
            $attempt->update(['last_activity_at' => now()]);
            Cache::put('attempt:'.$attempt->id.':heartbeat', now()->timestamp, 600);
        }

        return response()->json(['status' => $attempt->fresh()->status, 'server_time' => now()->toIso8601String()]);
    }

    public function submit(Request $request, ExamAttempt $attempt, AttemptFinalizer $finalizer)
    {
        $registration = $this->registration($request);
        abort_unless($attempt->registration_id === $registration->id, 403);
        $finalizer->finalize($attempt, now()->gte($attempt->expires_at) ? 'auto' : 'manual');

        return redirect()->route('results')->with('success', 'Jawaban berhasil dikirim dan menunggu finalisasi hasil.');
    }
}
