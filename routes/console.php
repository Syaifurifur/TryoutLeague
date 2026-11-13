<?php

use App\Models\ExamAttempt;
use App\Services\AttemptFinalizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attempts:expire', function () {
    ExamAttempt::query()
        ->where('status', 'in_progress')
        ->where('expires_at', '<=', now())
        ->orderBy('id')
        ->chunkById(100, function ($attempts) {
            foreach ($attempts as $attempt) {
                app(AttemptFinalizer::class)->finalize($attempt, 'auto');
            }
        });
})->purpose('Auto-submit attempt yang melewati batas waktu');

Schedule::command('attempts:expire')->everyMinute()->withoutOverlapping();
