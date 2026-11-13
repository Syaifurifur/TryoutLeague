<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SchoolController extends Controller
{
    public function dashboard(Request $request)
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $school = DB::table('schools')->find($schoolId);
        $participants = DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('users as u', 'u.id', '=', 'p.user_id')->where('p.school_id', $schoolId)->select('r.id', 'r.participant_number', 'r.status', 'u.name', 'u.email', DB::raw('(select count(*) from exam_attempts a where a.registration_id=r.id) as started'), DB::raw("(select count(*) from exam_attempts a where a.registration_id=r.id and a.status='scored') as completed"), DB::raw("(select AVG(s.scaled_score) from scores s join exam_attempts a on a.id=s.attempt_id where a.registration_id=r.id and s.status='finalized') as average_score"))->get();
        $scores = $participants->pluck('average_score')->filter()->map(fn ($value) => (float) $value)->sort()->values();
        $stats = ['registered' => $participants->count(), 'verified' => $participants->whereIn('status', ['verified', 'eligible'])->count(), 'started' => $participants->where('started', '>', 0)->count(), 'completed' => $participants->where('completed', '>', 0)->count(), 'average' => $scores->count() ? round($scores->avg(), 2) : null, 'median' => $scores->count() ? $scores->median() : null];
        $domains = DB::table('scores as s')->join('exam_attempts as a', 'a.id', '=', 's.attempt_id')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->where('p.school_id', $schoolId)->where('s.status', 'finalized')->pluck('s.components')->flatMap(fn ($json) => collect(json_decode($json, true) ?: [])->map(fn ($value, $domain) => ['domain' => $domain, 'percentage' => $value['percentage'] ?? 0]))->groupBy('domain')->map(fn ($rows, $domain) => ['domain' => $domain, 'average' => round($rows->avg('percentage'), 2)])->sortBy('average')->values();
        $subjectScores = DB::table('scores as score')->join('exam_attempts as a', 'a.id', '=', 'score.attempt_id')->join('event_registrations as r', 'r.id', '=', 'a.registration_id')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('exam_sessions as es', 'es.id', '=', 'a.exam_session_id')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('subjects as s', 's.id', '=', 'ep.subject_id')->where('p.school_id', $schoolId)->where('score.status', 'finalized')->groupBy('s.id', 's.name', 's.color')->select('s.name', 's.color', DB::raw('ROUND(AVG(score.scaled_score), 2) as average'))->get();
        $topPerformers = $participants->whereNotNull('average_score')->sortByDesc('average_score')->take(5)->values();
        $distribution = collect([['label' => '200–399', 'min' => 200, 'max' => 399], ['label' => '400–599', 'min' => 400, 'max' => 599], ['label' => '600–699', 'min' => 600, 'max' => 699], ['label' => '700–800', 'min' => 700, 'max' => 800]])->map(fn ($range) => ['label' => $range['label'], 'count' => $scores->filter(fn ($score) => $score >= $range['min'] && $score <= $range['max'])->count()]);
        $eventAverage = round((float) DB::table('scores')->where('status', 'finalized')->avg('scaled_score'), 2);

        return Inertia::render('School/Dashboard', ['school' => $school, 'stats' => $stats, 'participants' => $participants, 'domains' => $domains, 'subjectScores' => $subjectScores, 'topPerformers' => $topPerformers, 'distribution' => $distribution, 'eventAverage' => $eventAverage, 'events' => DB::table('events')->where('allow_school_registration', true)->get(['id', 'name'])]);
    }
}
