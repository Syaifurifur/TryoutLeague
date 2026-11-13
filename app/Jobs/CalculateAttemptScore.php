<?php

namespace App\Jobs;

use App\Models\ExamAttempt;
use App\Services\ScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateAttemptScore implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $attemptId) {}

    public function handle(ScoringService $scoring): void
    {
        if ($attempt = ExamAttempt::find($this->attemptId)) {
            $scoring->calculate($attempt);
        }
    }
}
