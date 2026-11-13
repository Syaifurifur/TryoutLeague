<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Score;
use App\Models\ScoringFormula;

class ScoringService
{
    public function calculate(ExamAttempt $attempt): Score
    {
        $attempt->load(['questions.question.options', 'questions.answer', 'registration', 'scoringFormula']);
        $formula = $attempt->scoringFormula ?: ScoringFormula::query()
            ->where('event_id', $attempt->registration->event_id)
            ->where('status', 'active')
            ->latest('version')
            ->first();

        $correctPoints = (float) ($formula?->correct_points ?? 1);
        $wrongPoints = (float) ($formula?->wrong_points ?? 0);
        $unansweredPoints = (float) ($formula?->unanswered_points ?? 0);
        $scaleMin = (float) ($formula?->scale_min ?? 200);
        $scaleMax = (float) ($formula?->scale_max ?? 800);
        $partialScoring = (bool) ($formula?->partial_scoring ?? false);
        $correct = $wrong = $unanswered = 0;
        $earned = $minimum = $maximum = 0.0;
        $components = [];

        foreach ($attempt->questions as $snapshot) {
            $question = $snapshot->question;
            $frozen = $snapshot->question_snapshot;
            $weight = (float) ($frozen['weight'] ?? $question->weight);
            $domain = ($frozen['domain'] ?? $question->domain) ?: 'Umum';
            $type = $frozen['type'] ?? $question->type;
            $components[$domain] ??= ['correct' => 0, 'wrong' => 0, 'unanswered' => 0, 'total' => 0, 'earned' => 0.0, 'possible' => 0.0];
            $components[$domain]['total']++;
            $components[$domain]['possible'] += $correctPoints * $weight;
            $minimum += min($wrongPoints, $unansweredPoints, 0) * $weight;
            $maximum += max($correctPoints, 0) * $weight;

            $selectedIds = collect($snapshot->answer?->selected_option_ids ?: ($snapshot->answer?->question_option_id ? [$snapshot->answer->question_option_id] : []))->map(fn ($id) => (int) $id)->unique()->sort()->values();
            $frozenOptions = collect($frozen['options'] ?? []);
            $correctIds = $frozenOptions->isNotEmpty()
                ? $frozenOptions->filter(fn ($option) => (bool) $option['is_correct'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()
                : $question->options->filter(fn ($option) => (bool) $option->getRawOriginal('is_correct'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            $optionCount = $frozenOptions->isNotEmpty() ? $frozenOptions->count() : $question->options->count();

            if ($selectedIds->isEmpty()) {
                $unanswered++;
                $earned += $unansweredPoints * $weight;
                $components[$domain]['unanswered']++;
                $components[$domain]['earned'] += $unansweredPoints * $weight;

                continue;
            }

            if ($type === 'matrix') {
                $matrixRows = collect($frozen['matrix_rows'] ?? $question->matrix_rows ?? []);
                $matrixAnswers = collect($snapshot->answer?->matrix_answers ?? []);
                $optionsByPosition = collect($frozen['options'] ?? $question->options->toArray())->values();
                $correctRows = $matrixRows->filter(function ($row, $index) use ($matrixAnswers, $optionsByPosition) {
                    $correctOptionId = $optionsByPosition->get((int) $row['correct'])['id'] ?? null;

                    return $correctOptionId !== null && (int) $matrixAnswers->get($index) === (int) $correctOptionId;
                })->count();
                if ($matrixRows->isNotEmpty() && $correctRows === $matrixRows->count()) {
                    $correct++;
                    $points = $correctPoints * $weight;
                    $components[$domain]['correct']++;
                } else {
                    $wrong++;
                    $points = $partialScoring && $matrixRows->isNotEmpty() ? $correctPoints * $weight * ($correctRows / $matrixRows->count()) : $wrongPoints * $weight;
                    $components[$domain]['wrong']++;
                }
                $earned += $points;
                $components[$domain]['earned'] += $points;

                continue;
            }

            if ($selectedIds->all() === $correctIds->all()) {
                $correct++;
                $points = $correctPoints * $weight;
                $components[$domain]['correct']++;
            } elseif ($type === 'multiple_choice' && $partialScoring) {
                $correctSelected = $selectedIds->intersect($correctIds)->count();
                $incorrectSelected = $selectedIds->diff($correctIds)->count();
                $incorrectOptions = max(1, $optionCount - $correctIds->count());
                $ratio = max(0, ($correctSelected / max(1, $correctIds->count())) - ($incorrectSelected / $incorrectOptions));
                $points = $correctPoints * $weight * $ratio;
                $wrong++;
                $components[$domain]['wrong']++;
            } else {
                $points = $wrongPoints * $weight;
                $wrong++;
                $components[$domain]['wrong']++;
            }

            $earned += $points;
            $components[$domain]['earned'] += $points;
        }

        $denominator = $maximum - $minimum;
        $raw = $denominator > 0 ? round(($earned - $minimum) / $denominator * 100, 2) : 0;
        $raw = max(0, min(100, $raw));
        $scaled = round($scaleMin + (($scaleMax - $scaleMin) * $raw / 100), 2);

        foreach ($components as &$component) {
            $component['percentage'] = $component['possible'] > 0 ? round(max(0, $component['earned']) / $component['possible'] * 100, 2) : 0;
        }
        unset($component);

        $score = Score::updateOrCreate(['attempt_id' => $attempt->id], [
            'scoring_formula_id' => $formula?->id,
            'raw_score' => $raw,
            'scaled_score' => $scaled,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'unanswered_count' => $unanswered,
            'percentile' => null,
            'status' => 'calculated',
            'components' => $components,
        ]);

        $attempt->update(['status' => 'scored', 'score_status' => 'calculated']);

        return $score;
    }
}
