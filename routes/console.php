<?php

declare(strict_types=1);

use App\Jobs\DeliverDueNotifications;
use App\Jobs\GenerateDueReminderOccurrences;
use App\Jobs\PurgeOrphanedFiles;
use App\Jobs\SendNotificationDigests;
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

/*
 * The concrete deadlines every live reminder rule produces inside the rolling
 * window. Once a day is enough: the window is measured in months, so a deadline
 * a few days out was written weeks ago, and running this every minute would find
 * the same nothing over and over. What matters is only that it runs at least
 * once inside the window, since a deadline that fell entirely inside a longer
 * gap is never invented after the fact.
 */
Schedule::job(new GenerateDueReminderOccurrences, 'low')->dailyAt('02:30');

/*
 * The deadlines that have come due, turned into notifications and handed to
 * their channels. Five minutes rather than an hour, because a reminder arriving
 * long after it was due is a reminder somebody stops trusting.
 */
Schedule::job(new DeliverDueNotifications, 'high')->everyFiveMinutes();

/*
 * The daily summary, for the accounts that asked for one instead of being told
 * a thing at a time. Hourly, and each account is judged against the hour it
 * chose, since that hour is local to it and no single moment in the day is when
 * every summary is due.
 */
Schedule::job(new SendNotificationDigests, 'low')->hourly();
