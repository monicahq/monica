<?php

use App\Console\Scheduling\Schedule;
use App\Domains\Contact\Dav\Jobs\CleanSyncToken;
use App\Domains\Contact\DavClient\Jobs\UpdateAddressBooks;
use App\Domains\Contact\ManageReminders\Jobs\ProcessScheduledContactReminders;
use App\Logging\CleanLogs;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Schedule::command('model:prune', 'daily');
Schedule::command('queue:prune-batches --hours=48 --unfinished=72 --cancelled=72', 'daily');
Schedule::command('queue:prune-failed --hours=48', 'daily');
if (config('telescope.enabled')) {
    Schedule::command('telescope:prune', 'daily');
}
Schedule::job(UpdateAddressBooks::class, 'hourly');
Schedule::job(ProcessScheduledContactReminders::class, 'minutes', 1);
Schedule::job(CleanSyncToken::class, 'daily');
Schedule::job(CleanLogs::class, 'daily');
