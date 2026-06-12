<?php

use App\Models\ExamAttempt;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| These run via `php artisan schedule:run`, which on a production server is
| invoked every minute by cron / Task Scheduler:
|   * * * * * cd /path/to/examcore && php artisan schedule:run >> /dev/null 2>&1
*/

// Auto-close abandoned exam attempts (alumno cerró pestaña, perdió internet, etc).
// Without this, those attempts stay 'in_progress' forever, blocking max_attempts,
// code regeneration, and grade sync. Runs every 5 minutes; SQL pre-filter keeps
// the cost minimal when nothing has actually expired.
Schedule::call(function () {
    $closed = ExamAttempt::closeTimedOutForAll();
    if ($closed > 0) {
        Log::info("[scheduler] Closed {$closed} timed-out exam attempt(s).");
    }
})->everyFiveMinutes()
  ->name('close-timed-out-attempts')
  ->withoutOverlapping(); // never run two copies at the same time
