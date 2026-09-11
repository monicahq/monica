<?php

declare(strict_types=1);

use App\Jobs\DeliverDueNotifications;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\GenerateDueReminderOccurrences;
use App\Jobs\PurgeOrphanedFiles;
use App\Jobs\SendNotificationDigests;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('01:00');
Schedule::command('model:prune')->dailyAt('01:05');
Schedule::command('queue:prune-batches')->dailyAt('01:10');
Schedule::command('queue:prune-failed')->dailyAt('01:15');

Schedule::job(new PurgeOrphanedFiles, 'low')->dailyAt('03:20');

Schedule::job(new DetectDuplicateContacts, 'low')->dailyAt('03:40');

Schedule::job(new GenerateDueReminderOccurrences, 'low')->dailyAt('02:30');

Schedule::job(new DeliverDueNotifications, 'high')->everyFiveMinutes();

Schedule::job(new SendNotificationDigests, 'low')->hourly();
