<?php

declare(strict_types=1);

use App\Jobs\PurgeOrphanedFiles;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * The files nothing points at any more, once they have sat there longer than
 * the grace period. Once a day is often enough: the period is counted in days,
 * and a sweep that ran every hour would only find the same nothing.
 */
Schedule::job(new PurgeOrphanedFiles, 'low')->dailyAt('03:20');
