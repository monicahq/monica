<?php

declare(strict_types=1);

use App\Jobs\PurgeOrphanedFiles;
use Illuminate\Support\Facades\Schedule;

/*
 * The following scheduled commands are for routine maintenance tasks.
 * They are set to run daily at specified times to keep the system clean
 * and efficient.
 */
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('01:00');
Schedule::command('model:prune')->dailyAt('01:05');
Schedule::command('queue:prune-batches')->dailyAt('01:10');
Schedule::command('queue:prune-failed')->dailyAt('01:15');

/*
 * The files nothing points at any more, once they have sat there longer than
 * the grace period. Once a day is often enough: the period is counted in days,
 * and a sweep that ran every hour would only find the same nothing.
 */
Schedule::job(new PurgeOrphanedFiles, 'low')->dailyAt('03:20');
