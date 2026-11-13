<?php

namespace App\Http\Controllers;

use App\Services\AttemptReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ParticipantController extends Controller
{
    private function context(Request $request): array
    {
        $participant = $request->user()->participant()->with('school')->firstOrFail();
        $registration = DB::table('event_registrations as er')
            ->join('events as e', 'e.id', '=', 'er.event_id')
            ->where('er.participant_id', $participant->id)
            ->orderByDesc('er.id')
            ->select('er.*', 'e.name as event_name', 'e.result_published_at', 'e.subject_selection_open_at', 'e.subject_selection_close_at', 'e.settings')
            ->firstOrFail();

        return [$participant, $registration];
    }

    public function dashboard(Request $request)
    {
        [$participant, $registration] = $this->context($request);
        $sessions = $this->sessionQuery($registration)->get();
        $scores = $sessions->whereNotNull('scaled_score')->pluck('scaled_score');
        $settings = json_decode($registration->settings ?: '{}', true);
        $ranking = ($settings['ranking'] ?? true) ? DB::table('rankings')->where('registration_id', $registration->id)->where('scope', 'national')->first() : null;
        $components = DB::table('scores as s')->join('exam_attempts as a', 'a.id', '=', 's.attempt_id')->where('a.registration_id', $registration->id)->pluck('s.components')->flatMap(fn ($json) => collect(json_decode($json, true) ?: [])->map(fn ($value, $domain) => ['domain' => $domain, 'percentage' => $value['percentage'] ?? (($value['correct'] ?? 0) / max(1, $value['total'] ?? 1) * 100)]))->sortBy('percentage')->values();

        return Inertia::render('Participant/Dashboard', [
            'participant' => ['name' => $request->user()->name, 'number' => $registration->participant_number, 'school' => $participant->school?->name, 'grade' => $participant->grade],
            'event' => $registration->event_name,
            'sessions' => $sessions,
            'progress' => $sessions->count() ? round($sessions->whereIn('attempt_status', ['submitted', 'scored'])->count() / $sessions->count() * 100) : 0,
            'average' => $scores->count() ? round($scores->avg()) : null,
            'nationalRank' => $ranking?->rank,
            'recommendation' => $components->first()['domain'] ?? null,
        ]);
    }

    public function schedule(Request $request)
    {
        [, $registration] = $this->context($request);

        return Inertia::render('Participant/Schedule', ['sessions' => $this->sessionQuery($registration)->get(), 'event' => $registration->event_name]);
    }

    public function subjects(Request $request)
    {
        [, $registration] = $this->context($request);
        $settings = json_decode($registration->settings ?: '{}', true);
        $subjects = DB::table('event_subjects as es')->join('subjects as s', 's.id', '=', 'es.subject_id')->leftJoin('participant_subjects as ps', fn ($join) => $join->on('ps.subject_id', '=', 's.id')->where('ps.registration_id', $registration->id))->where('es.event_id', $registration->event_id)->select('s.*', 'es.is_required', 'ps.id as selected_id')->get();
        $canEdit = (! $registration->subject_selection_open_at || now()->gte($registration->subject_selection_open_at)) && (! $registration->subject_selection_close_at || now()->lte($registration->subject_selection_close_at));

        return Inertia::render('Participant/Subjects', ['subjects' => $subjects, 'maxOptional' => (int) ($settings['max_optional_subjects'] ?? 2), 'canEdit' => $canEdit, 'closesAt' => $registration->subject_selection_close_at]);
    }

    public function updateSubjects(Request $request)
    {
        $data = $request->validate(['subjects' => ['array'], 'subjects.*' => ['integer', 'distinct', 'exists:subjects,id']]);
        [, $registration] = $this->context($request);
        if (($registration->subject_selection_open_at && now()->lt($registration->subject_selection_open_at)) || ($registration->subject_selection_close_at && now()->gt($registration->subject_selection_close_at))) {
            throw ValidationException::withMessages(['subjects' => 'Periode pemilihan mata uji sedang ditutup.']);
        }

        $available = DB::table('event_subjects')->where('event_id', $registration->event_id)->pluck('subject_id');
        $required = DB::table('event_subjects')->where('event_id', $registration->event_id)->where('is_required', true)->pluck('subject_id');
        $requested = collect($data['subjects'] ?? [])->map(fn ($id) => (int) $id);
        if ($requested->diff($available)->isNotEmpty()) {
            throw ValidationException::withMessages(['subjects' => 'Terdapat mata uji yang bukan bagian dari event.']);
        }
        $settings = json_decode($registration->settings ?: '{}', true);
        $maxOptional = (int) ($settings['max_optional_subjects'] ?? 2);
        if ($requested->diff($required)->count() > $maxOptional) {
            throw ValidationException::withMessages(['subjects' => "Maksimal {$maxOptional} mata uji pilihan."]);
        }
        $ids = $requested->merge($required)->unique()->values();
        $before = DB::table('participant_subjects')->where('registration_id', $registration->id)->pluck('subject_id')->map(fn ($id) => (int) $id)->values();

        DB::transaction(function () use ($request, $registration, $ids, $required, $before) {
            DB::table('participant_subjects')->where('registration_id', $registration->id)->delete();
            foreach ($ids as $id) {
                DB::table('participant_subjects')->insert(['registration_id' => $registration->id, 'subject_id' => $id, 'is_locked' => $required->contains($id), 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('subject_selection_histories')->insert(['registration_id' => $registration->id, 'user_id' => $request->user()->id, 'before_subject_ids' => json_encode($before), 'after_subject_ids' => json_encode($ids), 'created_at' => now()]);
        });

        return back()->with('success', 'Pilihan mata uji berhasil disimpan.');
    }

    public function results(Request $request, AttemptReviewService $reviews)
    {
        [$participant, $registration] = $this->context($request);
        $published = (bool) $registration->result_published_at && now()->gte($registration->result_published_at);
        $settings = json_decode($registration->settings ?: '{}', true);
        $scores = collect();
        $ranking = null;
        $discussion = $reviews->forRegistration($registration->id);
        if ($published) {
            $scores = DB::table('scores as sc')->join('exam_attempts as ea', 'ea.id', '=', 'sc.attempt_id')->join('exam_sessions as es', 'es.id', '=', 'ea.exam_session_id')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->where('ea.registration_id', $registration->id)->where('sc.status', 'finalized')->select('sc.*', 's.name as subject', 's.color', 'ep.duration_minutes')->get();
            $ranking = ($settings['ranking'] ?? true) ? DB::table('rankings')->where('registration_id', $registration->id)->where('scope', 'national')->first() : null;
        }

        $domains = $scores->flatMap(fn ($score) => collect(json_decode($score->components, true) ?: [])->map(fn ($value, $domain) => ['domain' => $domain, ...$value]))->sortByDesc('percentage')->values();
        $history = DB::table('event_registrations as r')->join('events as e', 'e.id', '=', 'r.event_id')->leftJoin('exam_attempts as a', 'a.registration_id', '=', 'r.id')->leftJoin('scores as s', fn ($join) => $join->on('s.attempt_id', '=', 'a.id')->where('s.status', 'finalized'))->where('r.participant_id', $participant->id)->groupBy('r.id', 'e.name', 'e.result_published_at')->select('r.id', 'e.name as event', 'e.result_published_at', DB::raw('AVG(s.scaled_score) as average_score'), DB::raw('COUNT(s.id) as scored_subjects'))->orderByDesc('r.id')->get();

        return Inertia::render('Participant/Results', ['participant' => ['name' => $request->user()->name, 'number' => $registration->participant_number, 'school' => $participant->school?->name], 'scores' => $scores, 'ranking' => $ranking, 'domains' => $domains, 'discussion' => $discussion, 'history' => $history, 'published' => $published]);
    }

    public function ranking(Request $request)
    {
        [$participant, $registration] = $this->context($request);
        abort_unless($registration->result_published_at && now()->gte($registration->result_published_at), 403, 'Ranking belum dipublikasikan.');
        $settings = json_decode($registration->settings ?: '{}', true);
        abort_unless($settings['ranking'] ?? true, 403, 'Ranking dinonaktifkan untuk event ini.');
        $filter = $request->validate(['scope' => ['nullable', Rule::in(['national', 'province', 'city', 'school', 'subject'])], 'subject_id' => ['nullable', 'integer', 'exists:subjects,id']]);
        $scope = $filter['scope'] ?? 'national';
        abort_unless(in_array($scope, $settings['ranking_scopes'] ?? ['national', 'province', 'city', 'school'], true), 403, 'Scope ranking tidak diaktifkan.');
        $subjects = DB::table('event_subjects as es')->join('subjects as s', 's.id', '=', 'es.subject_id')->where('es.event_id', $registration->event_id)->get(['s.id', 's.name']);
        $subjectId = $scope === 'subject' ? (int) ($filter['subject_id'] ?? $subjects->first()?->id) : null;
        if ($scope === 'subject') {
            abort_unless($subjects->contains('id', $subjectId), 403, 'Mata uji bukan bagian event.');
        }
        $scopeKey = $scope === 'subject' ? 'subject:'.$subjectId : $scope;
        $base = DB::table('rankings as r')->join('event_registrations as er', 'er.id', '=', 'r.registration_id')->join('participants as p', 'p.id', '=', 'er.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->leftJoin('schools as s', 's.id', '=', 'p.school_id')->where('r.event_id', $registration->event_id)->where('r.scope', $scopeKey);
        if ($scope === 'province') {
            $base->where('p.province', $participant->province);
        }
        if ($scope === 'city') {
            $base->where('p.city', $participant->city);
        }
        if ($scope === 'school') {
            $base->where('p.school_id', $participant->school_id);
        }
        $base->select('r.rank', 'r.score', 'u.name', 'er.participant_number', 's.name as school', 'r.registration_id');
        $rows = (clone $base)->orderBy('r.rank')->limit(50)->get();
        $mine = (clone $base)->where('r.registration_id', $registration->id)->first();
        if ($mine && ! $rows->contains('registration_id', $registration->id)) {
            $rows->push($mine);
        }
        $privacy = $settings['ranking_privacy'] ?? 'full_name';
        if ($privacy === 'self_only') {
            $rows = $rows->where('registration_id', $registration->id)->values();
        }
        $rows->each(function ($row) use ($privacy, $registration) {
            if ($row->registration_id === $registration->id) {
                return;
            }
            if ($privacy === 'participant_number') {
                $row->name = $row->participant_number;
            }
            if ($privacy === 'masked_name') {
                $row->name = mb_substr($row->name, 0, 1).str_repeat('*', max(3, mb_strlen($row->name) - 1));
            }
        });

        return Inertia::render('Participant/Ranking', ['rows' => $rows, 'mine' => $registration->id, 'scope' => $scope, 'subjects' => $subjects, 'subjectId' => $subjectId]);
    }

    public function certificate(Request $request)
    {
        [$participant, $registration] = $this->context($request);
        abort_unless($registration->result_published_at && now()->gte($registration->result_published_at), 403, 'Sertifikat belum tersedia.');
        $settings = json_decode($registration->settings ?: '{}', true);
        abort_unless($settings['certificate'] ?? true, 403, 'Sertifikat dinonaktifkan untuk event ini.');
        $certificate = DB::table('certificates as c')->leftJoin('certificate_templates as ct', 'ct.id', '=', 'c.certificate_template_id')->where('c.registration_id', $registration->id)->select('c.*', 'ct.title', 'ct.subtitle', 'ct.primary_color', 'ct.accent_color', 'ct.body_text', 'ct.signatory_name', 'ct.signatory_title')->firstOrFail();
        $score = DB::table('scores as s')->join('exam_attempts as a', 'a.id', '=', 's.attempt_id')->where('a.registration_id', $registration->id)->where('s.status', 'finalized')->avg('s.scaled_score');

        return Inertia::render('Participant/Certificate', ['certificate' => $certificate, 'participant' => ['name' => $request->user()->name, 'number' => $registration->participant_number, 'school' => $participant->school?->name], 'event' => $registration->event_name, 'score' => $score]);
    }

    public function profile(Request $request)
    {
        [$participant] = $this->context($request);

        return Inertia::render('Participant/Profile', ['participant' => $participant, 'user' => $request->user()->only(['name', 'email', 'phone'])]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'max:120'], 'phone' => ['required', 'max:30', Rule::unique('users')->ignore($request->user()->id)], 'grade' => ['required', 'max:10'], 'province' => ['required', 'max:120'], 'city' => ['required', 'max:120']]);
        [$participant] = $this->context($request);
        DB::transaction(function () use ($request, $participant, $data) {
            $request->user()->update(['name' => $data['name'], 'phone' => $data['phone']]);
            $participant->update(['grade' => $data['grade'], 'province' => $data['province'], 'city' => $data['city']]);
        });

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function notifications(Request $request)
    {
        [, $registration] = $this->context($request);
        $notifications = collect([
            [
                'id' => 'registration-'.$registration->id,
                'title' => 'Pendaftaran berhasil',
                'message' => "Nomor peserta {$registration->participant_number} berstatus {$registration->status}.",
                'time' => $registration->created_at,
                'type' => 'success',
                'href' => '/dashboard',
            ],
        ]);

        $this->sessionQuery($registration)->get()->each(function ($session) use ($notifications) {
            $notifications->push([
                'id' => 'session-'.$session->id,
                'title' => $session->attempt_status ? 'Progres tryout' : 'Jadwal tryout',
                'message' => $session->attempt_status
                    ? "{$session->subject}: status {$session->attempt_status}."
                    : "{$session->subject} tersedia mulai ".date('d M Y H:i', strtotime($session->starts_at)).'.',
                'time' => $session->starts_at,
                'type' => $session->attempt_status === 'scored' ? 'success' : 'info',
                'href' => '/schedule',
            ]);
        });

        if ($registration->result_published_at && now()->gte($registration->result_published_at)) {
            $notifications->push(['id' => 'result-'.$registration->id, 'title' => 'Hasil telah terbit', 'message' => 'Nilai, analisis kompetensi, dan ranking sudah dapat dilihat.', 'time' => $registration->result_published_at, 'type' => 'success', 'href' => '/results']);
        }
        if (DB::table('certificates')->where('registration_id', $registration->id)->whereNull('revoked_at')->exists()) {
            $notifications->push(['id' => 'certificate-'.$registration->id, 'title' => 'Sertifikat tersedia', 'message' => 'Sertifikat digital dengan kode verifikasi sudah dapat diunduh.', 'time' => $registration->result_published_at, 'type' => 'success', 'href' => '/certificate']);
        }

        return Inertia::render('Participant/Notifications', ['notifications' => $notifications->sortByDesc('time')->values()]);
    }

    private function sessionQuery(object $registration)
    {
        return DB::table('exam_sessions as es')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->join('participant_subjects as ps', fn ($join) => $join->on('ps.subject_id', '=', 'ep.subject_id')->where('ps.registration_id', $registration->id))->leftJoin('exam_attempts as ea', fn ($join) => $join->on('ea.exam_session_id', '=', 'es.id')->where('ea.registration_id', $registration->id))->leftJoin('scores as sc', 'sc.attempt_id', '=', 'ea.id')->where('ep.event_id', $registration->event_id)->select('es.id', 'es.name', 'es.starts_at', 'es.ends_at', 'es.status', 'ep.duration_minutes', 's.name as subject', 's.color', 'ea.id as attempt_id', 'ea.status as attempt_status', 'sc.scaled_score')->orderBy('es.starts_at');
    }
}
