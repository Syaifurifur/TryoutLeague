<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\PtnProgram;

class PtnRationalizationService
{
    public function evaluate(Participant $participant, float $userScore): array
    {
        $targetIds = array_values(array_filter([
            $participant->target_program_1_id,
            $participant->target_program_2_id,
        ]));

        if (empty($targetIds)) {
            return [
                'targets' => [],
                'recommendations' => $this->recommendations($userScore),
            ];
        }

        $programs = PtnProgram::whereIn('id', $targetIds)->get()->keyBy('id');
        $targets = [];

        foreach ([1 => $participant->target_program_1_id, 2 => $participant->target_program_2_id] as $slot => $programId) {
            if (! $programId || ! $programs->has($programId)) {
                continue;
            }
            $program = $programs->get($programId);
            $diff = round($userScore - (float) $program->passing_score, 1);
            $chance = (int) max(10, min(96, round(100 / (1 + exp(-$diff / 25)))));

            $status = match (true) {
                $diff >= 15 => 'Peluang Tinggi',
                $diff >= -20 => 'Kompetitif',
                default => 'Perlu Ditingkatkan',
            };

            $targets[] = [
                'slot' => $slot,
                'id' => $program->id,
                'university_name' => $program->university_name,
                'program_name' => $program->program_name,
                'degree' => $program->degree,
                'category' => $program->category,
                'passing_score' => (float) $program->passing_score,
                'user_score' => round($userScore, 1),
                'diff' => $diff,
                'chance' => $chance,
                'status' => $status,
                'quota' => $program->quota,
                'applicants' => $program->applicants,
            ];
        }

        return [
            'targets' => $targets,
            'recommendations' => $this->recommendations($userScore, $targetIds),
        ];
    }

    private function recommendations(float $userScore, array $excludeIds = []): array
    {
        if ($userScore <= 0) {
            return [];
        }

        return PtnProgram::query()
            ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->where('passing_score', '<=', $userScore + 15)
            ->orderByDesc('passing_score')
            ->limit(3)
            ->get()
            ->map(fn (PtnProgram $p) => [
                'id' => $p->id,
                'university_name' => $p->university_name,
                'program_name' => $p->program_name,
                'degree' => $p->degree,
                'passing_score' => (float) $p->passing_score,
            ])
            ->values()
            ->all();
    }
}
