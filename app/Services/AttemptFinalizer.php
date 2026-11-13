<?php

namespace App\Services;

use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttemptFinalizer
{
    public function finalize(ExamAttempt $attempt, string $submitType = 'manual'): ExamAttempt
    {
        Cache::lock('attempt-submit:'.$attempt->id, 15)->block(5, function () use ($attempt, $submitType) {
            DB::transaction(function () use ($attempt, $submitType) {
                $locked = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
                if (in_array($locked->status, ['submitted', 'scored'], true)) {
                    return;
                }

                $locked->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'auto_submitted' => $submitType === 'auto',
                    'submit_type' => $submitType,
                    'score_status' => 'processing',
                    'last_activity_at' => now(),
                ]);
                app(ScoringService::class)->calculate($locked);
            });
        });

        return $attempt->fresh();
    }
}
