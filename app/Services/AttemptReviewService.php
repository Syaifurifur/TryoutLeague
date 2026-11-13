<?php

namespace App\Services;

use App\Models\ExamAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttemptReviewService
{
    public function forRegistration(int $registrationId): Collection
    {
        $attempts = ExamAttempt::where('registration_id', $registrationId)
            ->whereIn('status', ['submitted', 'scored'])
            ->whereNotNull('submitted_at')
            ->with(['questions.question.options', 'questions.question.subject', 'questions.answer'])
            ->orderByDesc('submitted_at')->get();
        $sessions = DB::table('exam_sessions')->whereIn('id', $attempts->pluck('exam_session_id'))->pluck('name', 'id');

        return $attempts->flatMap(function ($attempt) use ($sessions) {
            return $attempt->questions->map(function ($snapshot) use ($attempt, $sessions) {
                $master = $snapshot->question;
                $frozen = $snapshot->question_snapshot ?? [];
                $originalOptions = collect($frozen['options'] ?? $master->options->map(fn ($option) => [
                    'id' => $option->id, 'content' => $option->content, 'is_correct' => (bool) $option->is_correct,
                ])->all())->values();
                $order = array_flip($snapshot->option_order ?? $originalOptions->pluck('id')->all());
                $selected = collect($snapshot->answer?->selected_option_ids ?? ($snapshot->answer?->question_option_id ? [$snapshot->answer->question_option_id] : []))->map(fn ($id) => (int) $id);
                $options = $originalOptions->sortBy(fn ($option) => $order[$option['id']] ?? PHP_INT_MAX)->values()->map(fn ($option, $index) => [
                    'id' => $option['id'], 'label' => chr(65 + $index), 'content' => $option['content'],
                    'is_correct' => (bool) $option['is_correct'], 'selected' => $selected->contains((int) $option['id']),
                ]);
                $type = $frozen['type'] ?? $master->type;
                $matrixAnswers = $snapshot->answer?->matrix_answers ?? [];
                $rows = collect($frozen['matrix_rows'] ?? $master->matrix_rows ?? [])->map(function ($row, $index) use ($originalOptions, $options, $matrixAnswers) {
                    $correctId = $originalOptions->get((int) $row['correct'])['id'] ?? null;
                    $chosenId = $matrixAnswers[$index] ?? null;

                    return [
                        'statement' => $row['statement'],
                        'selected' => $options->firstWhere('id', $chosenId),
                        'correct' => $options->firstWhere('id', $correctId),
                        'is_correct' => $chosenId !== null && (int) $chosenId === (int) $correctId,
                    ];
                })->values();
                $correctIds = $options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $isCorrect = $type === 'matrix'
                    ? $rows->isNotEmpty() && $rows->every(fn ($row) => $row['is_correct'])
                    : $selected->isNotEmpty() && $selected->unique()->sort()->values()->all() === $correctIds;
                $empty = $type === 'matrix' ? empty($matrixAnswers) : $selected->isEmpty();

                return [
                    'id' => $snapshot->id, 'attempt_id' => $attempt->id, 'position' => $snapshot->position,
                    'session' => $sessions[$attempt->exam_session_id] ?? 'Tryout',
                    'subject' => $frozen['subject'] ?? $master->subject->name,
                    'code' => $frozen['code'] ?? $master->code, 'type' => $type,
                    'content' => $frozen['content'] ?? $master->content,
                    'image_path' => array_key_exists('image_path', $frozen) ? $frozen['image_path'] : $master->image_path,
                    // Legacy attempts did not snapshot explanations; their question version is kept immutable.
                    'explanation' => array_key_exists('explanation', $frozen) ? $frozen['explanation'] : $master->explanation,
                    'options' => $options, 'matrix_rows' => $rows,
                    'selected_labels' => $options->where('selected', true)->pluck('label')->values(),
                    'correct_labels' => $options->where('is_correct', true)->pluck('label')->values(),
                    'result' => $empty ? 'unanswered' : ($isCorrect ? 'correct' : 'wrong'),
                ];
            });
        })->values();
    }
}
