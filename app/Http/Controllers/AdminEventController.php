<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ExamAttempt;
use App\Services\RankingService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminEventController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateEvent($request);
        $maxOptionalSubjects = (int) $data['max_optional_subjects'];
        $bannerPath = $request->file('banner')?->store('events', 'public');
        unset($data['max_optional_subjects'], $data['banner']);
        $event = Event::create([...$data, 'banner_path' => $bannerPath, 'code' => $data['code'] ?: strtoupper(Str::random(8)), 'slug' => $data['slug'] ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4)), 'settings' => ['max_optional_subjects' => $maxOptionalSubjects, 'ranking' => true, 'ranking_scopes' => ['national', 'province', 'city', 'school', 'subject'], 'ranking_privacy' => 'full_name', 'tie_break' => ['score', 'correct_count', 'submitted_at'], 'certificate' => true, 'show_discussion' => false, 'fullscreen_required' => false, 'max_concurrent_sessions' => 1], 'created_by' => $request->user()->id]);
        DB::table('scoring_formulas')->insert(['event_id' => $event->id, 'version' => 1, 'correct_points' => 1, 'wrong_points' => 0, 'unanswered_points' => 0, 'scale_min' => 200, 'scale_max' => 800, 'partial_scoring' => false, 'status' => 'active', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit($request, 'event.created', $event->id, $event->toArray());

        return back()->with('success', 'Event berhasil dibuat.');
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validateEvent($request, $event->id);
        $before = $event->toArray();
        $settings = [...($event->settings ?? []), 'max_optional_subjects' => (int) $data['max_optional_subjects']];
        $bannerPath = $request->file('banner')?->store('events', 'public') ?: $event->banner_path;
        unset($data['max_optional_subjects'], $data['banner']);
        $event->update([...$data, 'banner_path' => $bannerPath, 'settings' => $settings]);
        $this->audit($request, 'event.updated', $event->id, ['before' => $before, 'after' => $event->fresh()->toArray()]);

        return back()->with('success', 'Konfigurasi event diperbarui.');
    }

    public function setStatus(Request $request, Event $event)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'closed', 'archived'])]]);
        $event->update($data);
        $this->audit($request, 'event.status', $event->id, $data);

        return back()->with('success', 'Status event diperbarui.');
    }

    public function attachSubject(Request $request, Event $event)
    {
        $data = $request->validate(['subject_id' => ['required', 'exists:subjects,id'], 'is_required' => ['required', 'boolean']]);
        DB::table('event_subjects')->updateOrInsert(['event_id' => $event->id, 'subject_id' => $data['subject_id']], ['is_required' => $data['is_required'], 'max_choices' => $data['is_required'] ? null : ($event->settings['max_optional_subjects'] ?? 2)]);

        return back()->with('success', 'Mata uji ditambahkan ke event.');
    }

    public function detachSubject(Event $event, int $subject)
    {
        abort_if(DB::table('exam_packages')->where('event_id', $event->id)->where('subject_id', $subject)->exists(), 409, 'Mata uji sudah digunakan oleh paket.');
        DB::table('event_subjects')->where('event_id', $event->id)->where('subject_id', $subject)->delete();

        return back()->with('success', 'Mata uji dilepas dari event.');
    }

    public function storeFormula(Request $request, Event $event)
    {
        $data = $request->validate(['correct_points' => ['required', 'numeric'], 'wrong_points' => ['required', 'numeric'], 'unanswered_points' => ['required', 'numeric'], 'scale_min' => ['required', 'numeric'], 'scale_max' => ['required', 'numeric', 'gt:scale_min'], 'partial_scoring' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $event, $data) {
            DB::table('scoring_formulas')->where('event_id', $event->id)->update(['status' => 'retired', 'updated_at' => now()]);
            $version = (int) DB::table('scoring_formulas')->where('event_id', $event->id)->max('version') + 1;
            DB::table('scoring_formulas')->insert([...$data, 'event_id' => $event->id, 'version' => $version, 'status' => 'active', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Formula scoring versi baru diaktifkan.');
    }

    public function updateRules(Request $request, Event $event)
    {
        $data = $request->validate([
            'ranking' => ['required', 'boolean'],
            'certificate' => ['required', 'boolean'],
            'show_discussion' => ['required', 'boolean'],
            'fullscreen_required' => ['required', 'boolean'],
            'max_concurrent_sessions' => ['required', 'integer', 'min:1', 'max:5'],
            'ranking_scopes' => ['required', 'array', 'min:1'],
            'ranking_scopes.*' => ['in:national,province,city,school,subject'],
            'ranking_privacy' => ['required', 'in:full_name,masked_name,participant_number,self_only'],
            'tie_break' => ['required', 'array', 'min:1'],
            'tie_break.*' => ['in:score,correct_count,submitted_at'],
        ]);
        $before = $event->settings ?? [];
        $event->update(['settings' => [...$before, ...$data]]);
        $this->audit($request, 'event.rules_updated', $event->id, ['before' => $before, 'after' => $event->fresh()->settings]);

        return back()->with('success', 'Aturan hasil, ranking, fullscreen, dan concurrent session diperbarui.');
    }

    public function finalizeResults(Request $request, Event $event, RankingService $ranking)
    {
        DB::transaction(function () use ($event, $ranking) {
            DB::table('scores')->whereIn('attempt_id', function ($query) use ($event) {
                $query->select('a.id')->from('exam_attempts as a')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->where('r.event_id', $event->id);
            })->where('status', 'calculated')->update(['status' => 'finalized', 'updated_at' => now()]);
            DB::table('exam_attempts')->whereIn('registration_id', DB::table('event_registrations')->where('event_id', $event->id)->select('id'))->where('score_status', 'calculated')->update(['score_status' => 'finalized', 'updated_at' => now()]);
            $this->updatePercentiles($event->id);
            $ranking->rebuild($event->id);
        });

        return back()->with('success', 'Scoring event berhasil difinalisasi.');
    }

    public function rescore(Request $request, Event $event, ScoringService $scoring)
    {
        $attemptIds = DB::table('exam_attempts as a')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->where('r.event_id', $event->id)->whereIn('a.status', ['submitted', 'scored'])->pluck('a.id');
        foreach ($attemptIds as $attemptId) {
            $scoring->calculate(ExamAttempt::findOrFail($attemptId));
        }
        $this->audit($request, 'event.rescored', $event->id, ['attempt_count' => $attemptIds->count()]);

        return back()->with('success', $attemptIds->count().' attempt dihitung ulang dari snapshot tanpa mengubah jawaban.');
    }

    public function publishResults(Request $request, Event $event)
    {
        abort_if(DB::table('scores as s')->join('exam_attempts as a', 'a.id', '=', 's.attempt_id')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->where('r.event_id', $event->id)->where('s.status', 'calculated')->exists(), 409, 'Finalisasi seluruh skor sebelum publikasi.');
        if ($event->settings['certificate'] ?? true) {
            $templateId = DB::table('certificate_templates')->where('is_default', true)->value('id');
            $eligible = DB::table('event_registrations as r')->join('exam_attempts as a', 'a.registration_id', '=', 'r.id')->join('scores as s', 's.attempt_id', '=', 'a.id')->where('r.event_id', $event->id)->where('r.status', 'eligible')->where('s.status', 'finalized')->distinct()->pluck('r.id');
            foreach ($eligible as $registrationId) {
                if (! DB::table('certificates')->where('registration_id', $registrationId)->exists()) {
                    DB::table('certificates')->insert(['registration_id' => $registrationId, 'certificate_template_id' => $templateId, 'verification_code' => 'CERT-'.strtoupper(Str::random(16)), 'issued_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
        $event->update(['result_published_at' => now()]);
        $this->audit($request, 'event.results_published', $event->id, ['published_at' => now()->toIso8601String()]);

        return back()->with('success', 'Hasil, ranking, dan sertifikat telah dipublikasikan.');
    }

    private function validateEvent(Request $request, ?int $eventId = null): array
    {
        return $request->validate([
            'name' => ['required', 'max:255'], 'code' => ['nullable', 'max:40', Rule::unique('events')->ignore($eventId)], 'slug' => ['nullable', 'max:255', Rule::unique('events')->ignore($eventId)], 'description' => ['nullable'], 'banner' => ['nullable', 'image', 'max:5120'], 'terms' => ['nullable'],
            'registration_open_at' => ['nullable', 'date'], 'registration_close_at' => ['nullable', 'date', 'after_or_equal:registration_open_at'], 'subject_selection_open_at' => ['nullable', 'date'], 'subject_selection_close_at' => ['nullable', 'date', 'after_or_equal:subject_selection_open_at'], 'event_start_at' => ['required', 'date'], 'event_end_at' => ['required', 'date', 'after:event_start_at'],
            'quota' => ['nullable', 'integer', 'min:1'], 'status' => ['required', Rule::in(['draft', 'published', 'closed', 'archived'])], 'allow_individual_registration' => ['required', 'boolean'], 'allow_school_registration' => ['required', 'boolean'], 'max_optional_subjects' => ['required', 'integer', 'min:0', 'max:20'],
        ]);
    }

    private function updatePercentiles(int $eventId): void
    {
        $scores = DB::table('scores as s')->join('exam_attempts as a', 'a.id', '=', 's.attempt_id')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->where('r.event_id', $eventId)->where('s.status', 'finalized')->orderBy('s.scaled_score')->pluck('s.scaled_score', 's.id');
        $total = $scores->count();
        foreach ($scores as $scoreId => $value) {
            $below = $scores->filter(fn ($score) => $score < $value)->count();
            DB::table('scores')->where('id', $scoreId)->update(['percentile' => $total ? round($below / $total * 100, 2) : null]);
        }
    }

    private function audit(Request $request, string $action, int $id, array $after): void
    {
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'entity_type' => 'event', 'entity_id' => $id, 'after' => json_encode($after), 'ip_address' => $request->ip(), 'created_at' => now()]);
    }
}
