<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function rebuild(int $eventId): void
    {
        $settings = json_decode((string) DB::table('events')->where('id', $eventId)->value('settings'), true) ?: [];
        if (! ($settings['ranking'] ?? true)) {
            DB::table('rankings')->where('event_id', $eventId)->delete();

            return;
        }
        $scopes = collect($settings['ranking_scopes'] ?? ['national', 'province', 'city', 'school']);
        $tieBreak = $settings['tie_break'] ?? ['score', 'correct_count', 'submitted_at'];
        $base = DB::table('event_registrations as r')
            ->join('participants as p', 'p.id', '=', 'r.participant_id')
            ->join('exam_attempts as a', 'a.registration_id', '=', 'r.id')
            ->join('scores as s', 's.attempt_id', '=', 'a.id')
            ->where('r.event_id', $eventId)
            ->where('s.status', 'finalized')
            ->select('r.id as registration_id', 'p.province', 'p.city', 'p.school_id', DB::raw('AVG(s.scaled_score) as score'), DB::raw('SUM(s.correct_count) as correct_count'), DB::raw('MAX(a.submitted_at) as submitted_at'))
            ->groupBy('r.id', 'p.province', 'p.city', 'p.school_id')
            ->get();
        $bySubject = DB::table('event_registrations as r')->join('participants as p', 'p.id', '=', 'r.participant_id')->join('exam_attempts as a', 'a.registration_id', '=', 'r.id')->join('exam_sessions as es', 'es.id', '=', 'a.exam_session_id')->join('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')->join('scores as s', 's.attempt_id', '=', 'a.id')->where('r.event_id', $eventId)->where('s.status', 'finalized')->select('r.id as registration_id', 'ep.subject_id', 'p.province', 'p.city', 'p.school_id', 's.scaled_score as score', 's.correct_count', 'a.submitted_at')->get();

        DB::table('rankings')->where('event_id', $eventId)->delete();
        if ($scopes->contains('national')) {
            $this->storeScope($eventId, 'national', collect([$base]), $tieBreak);
        }
        if ($scopes->contains('province')) {
            $this->storeScope($eventId, 'province', $base->groupBy(fn ($row) => $row->province ?: 'Tidak diketahui'), $tieBreak);
        }
        if ($scopes->contains('city')) {
            $this->storeScope($eventId, 'city', $base->groupBy(fn ($row) => $row->city ?: 'Tidak diketahui'), $tieBreak);
        }
        if ($scopes->contains('school')) {
            $this->storeScope($eventId, 'school', $base->groupBy(fn ($row) => (string) ($row->school_id ?: 0)), $tieBreak);
        }
        if ($scopes->contains('subject')) {
            foreach ($bySubject->groupBy('subject_id') as $subjectId => $rows) {
                $this->storeScope($eventId, 'subject:'.$subjectId, collect([$rows]), $tieBreak);
            }
        }
    }

    private function storeScope(int $eventId, string $scope, Collection $groups, array $tieBreak): void
    {
        foreach ($groups as $rows) {
            $sorted = collect($rows)->sort(function ($a, $b) use ($tieBreak) {
                foreach ($tieBreak as $field) {
                    $comparison = match ($field) {
                        'correct_count' => (int) $b->correct_count <=> (int) $a->correct_count,
                        'submitted_at' => strcmp((string) $a->submitted_at, (string) $b->submitted_at),
                        default => (float) $b->score <=> (float) $a->score,
                    };
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return $a->registration_id <=> $b->registration_id;
            })->values();
            $rank = 0;
            $previousSignature = null;
            foreach ($sorted as $index => $row) {
                $signature = collect($tieBreak)->map(fn ($field) => match ($field) {
                    'correct_count' => (int) $row->correct_count,
                    'submitted_at' => (string) $row->submitted_at,
                    default => (float) $row->score,
                })->all();
                if ($previousSignature === null || $signature !== $previousSignature) {
                    $rank = $index + 1;
                    $previousSignature = $signature;
                }
                DB::table('rankings')->insert([
                    'event_id' => $eventId,
                    'registration_id' => $row->registration_id,
                    'scope' => $scope,
                    'rank' => $rank,
                    'total_participants' => $sorted->count(),
                    'score' => $row->score,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
