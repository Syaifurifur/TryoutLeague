<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = Cache::remember('admin:kpi', 60, fn () => ['participants' => DB::table('event_registrations')->count(), 'completed' => DB::table('exam_attempts')->whereIn('status', ['submitted', 'scored'])->count(), 'average' => round((float) DB::table('scores')->avg('scaled_score')), 'schools' => DB::table('schools')->count()]);
        $distribution = [['range' => '200–399', 'value' => DB::table('scores')->whereBetween('scaled_score', [200, 399])->count()], ['range' => '400–599', 'value' => DB::table('scores')->whereBetween('scaled_score', [400, 599])->count()], ['range' => '600–699', 'value' => DB::table('scores')->whereBetween('scaled_score', [600, 699])->count()], ['range' => '700–800', 'value' => DB::table('scores')->whereBetween('scaled_score', [700, 800])->count()]];
        $recent = DB::table('exam_attempts as a')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->select('u.name', 'r.participant_number', 'a.status', 'a.started_at')->latest('a.started_at')->limit(6)->get();
        $trend = DB::table('event_registrations')->where('created_at', '>=', now()->subDays(6)->startOfDay())->selectRaw('DATE(created_at) as date, COUNT(*) as value')->groupByRaw('DATE(created_at)')->pluck('value', 'date');
        $trend = collect(range(6, 0))->map(fn ($days) => ['date' => now()->subDays($days)->format('Y-m-d'), 'label' => now()->subDays($days)->translatedFormat('D'), 'value' => (int) ($trend[now()->subDays($days)->format('Y-m-d')] ?? 0)]);

        return Inertia::render('Admin/Dashboard', ['stats' => $stats, 'distribution' => $distribution, 'recent' => $recent, 'trend' => $trend]);
    }

    public function participants(Request $request)
    {
        $rows = DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->leftJoin('schools as s', 's.id', '=', 'p.school_id')->when($request->q, fn ($q, $v) => $q->where(fn ($x) => $x->where('u.name', 'like', "%$v%")->orWhere('r.participant_number', 'like', "%$v%")))->select('r.id', 'r.participant_number', 'u.name', 'u.email', 's.name as school', 'r.status', 'r.source')->latest('r.id')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Participants', ['participants' => $rows, 'filters' => $request->only('q'), 'events' => DB::table('events')->orderByDesc('id')->get(['id', 'name']), 'schools' => DB::table('schools')->orderBy('name')->get(['id', 'name'])]);
    }

    public function setParticipantStatus(Request $request, int $registration)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,verified,eligible,cancelled']]);
        $before = DB::table('event_registrations')->find($registration);
        DB::table('event_registrations')->where('id', $registration)->update(['status' => $data['status'], 'updated_at' => now()]);
        $this->audit($request, 'participant.status', 'event_registration', $registration, $before, ['status' => $data['status']]);

        return back()->with('success', 'Status peserta diperbarui.');
    }

    public function questions(Request $request)
    {
        $rows = Question::with(['subject', 'options'])->when($request->q, fn ($q, $v) => $q->where(fn ($x) => $x->where('code', 'like', "%$v%")->orWhere('content', 'like', "%$v%")))->when($request->status, fn ($query, $status) => $query->where('status', $status))->latest()->paginate(10)->withQueryString();
        $comments = DB::table('question_comments as c')->join('users as u', 'u.id', '=', 'c.user_id')->whereIn('c.question_id', $rows->getCollection()->pluck('id'))->orderBy('c.created_at')->get(['c.id', 'c.question_id', 'c.comment', 'c.created_at', 'u.name as author'])->groupBy('question_id');
        $rows->getCollection()->each(fn ($question) => $question->setAttribute('comments', $comments->get($question->id, collect())->values()));
        // Answer keys are visible only in this role-protected editor payload.
        $rows->getCollection()->each(fn ($question) => $question->options->each(fn ($option) => $option->makeVisible('is_correct')));

        return Inertia::render('Admin/Questions', ['questions' => $rows, 'subjects' => DB::table('subjects')->get(['id', 'name']), 'filters' => $request->only('q', 'status')]);
    }

    public function storeQuestion(Request $request)
    {
        $data = $request->validate(['subject_id' => ['required', 'exists:subjects,id'], 'domain' => ['required', 'max:120'], 'subdomain' => ['nullable', 'max:120'], 'tags' => ['nullable', 'array'], 'difficulty' => ['required', 'in:easy,medium,hard'], 'type' => ['nullable', 'in:single_choice,multiple_choice,matrix'], 'content' => ['required', 'string'], 'matrix_rows' => ['nullable', 'array'], 'matrix_rows.*.statement' => ['required_if:type,matrix', 'string', 'max:1000'], 'matrix_rows.*.correct' => ['required_if:type,matrix', 'integer', 'min:0'], 'image' => ['nullable', 'image', 'max:5120'], 'explanation' => ['nullable', 'string'], 'options' => ['required', 'array', 'min:2', 'max:10'], 'options.*' => ['required', 'string'], 'correct' => ['required']]);
        $this->validateQuestionShape($data);
        $imagePath = $request->file('image')?->store('questions', 'public');
        DB::transaction(function () use ($data, $request, $imagePath, &$question) {
            $correct = is_array($data['correct']) ? collect($data['correct'])->map(fn ($value) => (int) $value)->all() : [(int) $data['correct']];
            $question = Question::create(['code' => 'Q-'.strtoupper(Str::random(8)), 'subject_id' => $data['subject_id'], 'domain' => $data['domain'], 'subdomain' => $data['subdomain'] ?? null, 'tags' => $data['tags'] ?? [], 'difficulty' => $data['difficulty'], 'type' => $data['type'] ?? 'single_choice', 'content' => $data['content'], 'matrix_rows' => ($data['type'] ?? null) === 'matrix' ? $data['matrix_rows'] : null, 'image_path' => $imagePath, 'explanation' => $data['explanation'] ?? null, 'status' => 'draft', 'created_by' => $request->user()->id]);
            foreach ($data['options'] as $i => $option) {
                DB::table('question_options')->insert(['question_id' => $question->id, 'label' => chr(65 + $i), 'content' => $option, 'is_correct' => in_array($i, $correct, true), 'position' => $i + 1]);
            }
            DB::table('question_versions')->insert(['question_id' => $question->id, 'version' => 1, 'snapshot' => json_encode($question->load('options')->toArray()), 'created_by' => $request->user()->id, 'created_at' => now()]);
        });
        $this->audit($request, 'question.created', 'question', $question->id, null, $question->toArray());

        return back()->with('success', 'Soal baru ditambahkan ke bank soal.');
    }

    public function setQuestionStatus(Request $request, Question $question)
    {
        $data = $request->validate(['status' => ['required', 'in:draft,review,approved,retired']]);
        if ($data['status'] === 'approved') {
            abort_unless(in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        }
        $before = $question->toArray();
        $question->update([...$data, 'reviewed_by' => in_array($data['status'], ['approved', 'retired'], true) ? $request->user()->id : null, 'reviewed_at' => in_array($data['status'], ['approved', 'retired'], true) ? now() : null]);
        $this->audit($request, 'question.status', 'question', $question->id, $before, $question->fresh()->toArray());

        return back()->with('success', 'Status soal diperbarui.');
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $data = $request->validate(['subject_id' => ['required', 'exists:subjects,id'], 'domain' => ['required', 'max:120'], 'subdomain' => ['nullable', 'max:120'], 'tags' => ['nullable', 'array'], 'difficulty' => ['required', 'in:easy,medium,hard'], 'type' => ['required', 'in:single_choice,multiple_choice,matrix'], 'content' => ['required', 'string'], 'matrix_rows' => ['nullable', 'array'], 'matrix_rows.*.statement' => ['required_if:type,matrix', 'string', 'max:1000'], 'matrix_rows.*.correct' => ['required_if:type,matrix', 'integer', 'min:0'], 'image' => ['nullable', 'image', 'max:5120'], 'explanation' => ['nullable', 'string'], 'options' => ['required', 'array', 'min:2', 'max:10'], 'options.*' => ['required', 'string'], 'correct' => ['required']]);
        $this->validateQuestionShape($data);
        $before = $question->load('options')->toArray();
        $imagePath = $request->file('image')?->store('questions', 'public') ?: $question->image_path;
        $immutable = DB::table('exam_package_questions as pq')->join('exam_packages as p', 'p.id', '=', 'pq.exam_package_id')->where('pq.question_id', $question->id)->where('p.status', 'published')->exists()
            || DB::table('attempt_questions')->where('question_id', $question->id)->exists();

        $savedQuestion = DB::transaction(function () use ($data, $request, $question, $imagePath, $immutable) {
            $correct = is_array($data['correct']) ? collect($data['correct'])->map(fn ($value) => (int) $value)->all() : [(int) $data['correct']];
            $attributes = ['subject_id' => $data['subject_id'], 'domain' => $data['domain'], 'subdomain' => $data['subdomain'] ?? null, 'tags' => $data['tags'] ?? [], 'difficulty' => $data['difficulty'], 'type' => $data['type'], 'content' => $data['content'], 'matrix_rows' => $data['type'] === 'matrix' ? $data['matrix_rows'] : null, 'image_path' => $imagePath, 'explanation' => $data['explanation'] ?? null, 'version' => $question->version + 1, 'status' => 'draft', 'reviewed_by' => null, 'reviewed_at' => null];
            if ($immutable) {
                $saved = Question::create([...$attributes, 'code' => Str::limit($question->code, 28, '').'-V'.($question->version + 1).'-'.Str::upper(Str::random(3)), 'created_by' => $request->user()->id]);
            } else {
                $question->update($attributes);
                $question->options()->delete();
                $saved = $question;
            }
            foreach ($data['options'] as $index => $option) {
                DB::table('question_options')->insert(['question_id' => $saved->id, 'label' => chr(65 + $index), 'content' => $option, 'is_correct' => in_array($index, $correct, true), 'position' => $index + 1]);
            }
            DB::table('question_versions')->insert(['question_id' => $saved->id, 'version' => $saved->version, 'snapshot' => json_encode($saved->fresh()->load('options')->toArray()), 'created_by' => $request->user()->id, 'created_at' => now()]);

            return $saved->fresh()->load('options');
        });
        $this->audit($request, $immutable ? 'question.version_created' : 'question.updated', 'question', $savedQuestion->id, $before, $savedQuestion->toArray());

        return back()->with('success', $immutable ? 'Soal sudah dipakai; versi baru dibuat sebagai draft tanpa mengubah paket lama.' : 'Versi baru soal disimpan sebagai draft.');
    }

    public function commentQuestion(Request $request, Question $question)
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        DB::table('question_comments')->insert(['question_id' => $question->id, 'user_id' => $request->user()->id, 'comment' => $data['comment'], 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Komentar reviewer ditambahkan.');
    }

    public function events()
    {
        $events = DB::table('events as e')->leftJoin('event_registrations as r', 'r.event_id', '=', 'e.id')->leftJoin('exam_packages as p', 'p.event_id', '=', 'e.id')->groupBy('e.id', 'e.name', 'e.code', 'e.slug', 'e.description', 'e.banner_path', 'e.terms', 'e.status', 'e.quota', 'e.settings', 'e.allow_individual_registration', 'e.allow_school_registration', 'e.registration_open_at', 'e.registration_close_at', 'e.subject_selection_open_at', 'e.subject_selection_close_at', 'e.event_start_at', 'e.event_end_at', 'e.result_published_at')->select('e.id', 'e.name', 'e.code', 'e.slug', 'e.description', 'e.banner_path', 'e.terms', 'e.status', 'e.quota', 'e.settings', 'e.allow_individual_registration', 'e.allow_school_registration', 'e.registration_open_at', 'e.registration_close_at', 'e.subject_selection_open_at', 'e.subject_selection_close_at', 'e.event_start_at', 'e.event_end_at', 'e.result_published_at', DB::raw('COUNT(DISTINCT r.id) as participants'), DB::raw('COUNT(DISTINCT p.id) as packages'))->get();
        foreach ($events as $event) {
            $event->subjects = DB::table('event_subjects as es')->join('subjects as s', 's.id', '=', 'es.subject_id')->where('es.event_id', $event->id)->get(['s.id', 's.name', 'es.is_required']);
            $event->exam_packages = DB::table('exam_packages as p')->join('subjects as s', 's.id', '=', 'p.subject_id')->where('p.event_id', $event->id)->select('p.*', 's.name as subject', DB::raw('(select count(*) from exam_package_questions pq where pq.exam_package_id=p.id) as question_count'))->get();
            $event->sessions = DB::table('exam_sessions as es')->join('exam_packages as p', 'p.id', '=', 'es.exam_package_id')->where('p.event_id', $event->id)->select('es.*', 'p.name as package_name')->get();
            $event->formula = DB::table('scoring_formulas')->where('event_id', $event->id)->where('status', 'active')->latest('version')->first();
        }

        return Inertia::render('Admin/Events', ['events' => $events, 'subjects' => DB::table('subjects')->orderBy('name')->get(), 'approvedQuestions' => DB::table('questions')->where('status', 'approved')->select('id', 'subject_id', 'code', 'domain', 'difficulty', 'content')->get()]);
    }

    public function monitoring()
    {
        $attempts = DB::table('exam_attempts as a')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->join('exam_sessions as es', 'es.id', '=', 'a.exam_session_id')->select('a.id', 'u.name', 'r.participant_number', 'es.name as session', 'a.status', 'a.expires_at', 'a.last_activity_at', 'a.tab_switch_count', DB::raw('(select count(*) from answers an join attempt_questions aq on aq.id=an.attempt_question_id where aq.attempt_id=a.id) as answered'))->latest('a.started_at')->get();
        $events = DB::table('integrity_events as i')->join('exam_attempts as a', 'a.id', '=', 'i.attempt_id')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->latest('i.created_at')->limit(30)->get(['i.id', 'i.event_type', 'i.ip_address', 'i.created_at', 'u.name']);
        $summary = [
            'not_logged_in' => DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->whereNull('u.last_login_at')->count(),
            'ready' => DB::table('event_registrations as r')->whereIn('r.status', ['verified', 'eligible'])->whereNotExists(fn ($query) => $query->selectRaw('1')->from('exam_attempts as a')->whereColumn('a.registration_id', 'r.id'))->count(),
            'active' => $attempts->where('status', 'in_progress')->count(),
            'connection_issues' => $attempts->where('status', 'in_progress')->filter(fn ($attempt) => ! $attempt->last_activity_at || now()->diffInSeconds($attempt->last_activity_at) > 30)->count(),
            'submitted' => $attempts->whereIn('status', ['submitted', 'scored'])->count(),
            'auto_submitted' => DB::table('exam_attempts')->where('auto_submitted', true)->count(),
        ];

        return Inertia::render('Admin/Monitoring', ['attempts' => $attempts, 'integrityEvents' => $events, 'summary' => $summary]);
    }

    private function audit(Request $request, string $action, string $type, int $id, $before, $after): void
    {
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'entity_type' => $type, 'entity_id' => $id, 'before' => $before ? json_encode($before) : null, 'after' => json_encode($after), 'ip_address' => $request->ip(), 'created_at' => now()]);
        Cache::forget('admin:kpi');
    }

    private function validateQuestionShape(array $data): void
    {
        if (($data['type'] ?? 'single_choice') !== 'matrix') {
            $keys = is_array($data['correct']) ? $data['correct'] : [$data['correct']];
            if (empty($keys) || collect($keys)->contains(fn ($key) => filter_var($key, FILTER_VALIDATE_INT) === false || (int) $key < 0 || (int) $key >= count($data['options']))) {
                throw ValidationException::withMessages(['correct' => 'Pilih kunci jawaban yang valid dari opsi soal.']);
            }
            if (count(array_unique($keys)) !== count($keys) || (($data['type'] ?? 'single_choice') === 'single_choice' && count($keys) !== 1)) {
                throw ValidationException::withMessages(['correct' => 'Soal tunggal harus memiliki tepat satu kunci; kunci ganda tidak boleh duplikat.']);
            }
        }
        if (($data['type'] ?? 'single_choice') !== 'matrix' && count($data['options']) < 4) {
            throw ValidationException::withMessages(['options' => 'Soal pilihan ganda minimal memiliki empat opsi.']);
        }
        if (($data['type'] ?? null) === 'matrix') {
            if (empty($data['matrix_rows'])) {
                throw ValidationException::withMessages(['matrix_rows' => 'Soal matriks minimal memiliki satu pernyataan.']);
            }
            if (collect($data['matrix_rows'])->contains(fn ($row) => (int) $row['correct'] >= count($data['options']))) {
                throw ValidationException::withMessages(['matrix_rows' => 'Kunci kategori matriks tidak valid.']);
            }
        }
    }
}
