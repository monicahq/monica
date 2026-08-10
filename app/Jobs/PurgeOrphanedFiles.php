<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DestroyFile;
use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sweep up the files nothing points at any more.
 *
 * A file goes orphan the moment the last thing holding it lets go, and it is
 * left alone for a grace period after that. The period exists so that restoring
 * a record to how it looked before still finds the file it pointed at; it is
 * configuration, and it has to stay longer than the window in which a record
 * can be restored.
 *
 * Anything that has been pointed at again since is skipped: the count is what
 * decides, and the date is only what says how long it has been that way.
 *
 * One entry goes in the log per account rather than one per file, since nobody
 * asked for this and a hundred lines saying the same thing is not a record of
 * anything.
 */
class PurgeOrphanedFiles implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cutoff = now()->subDays((int) config('monica.orphaned_file_grace_period_in_days'));

        File::query()
            ->where('reference_count', 0)
            ->whereNotNull('orphaned_at')
            ->where('orphaned_at', '<=', $cutoff)
            ->with('account')
            ->get()
            ->groupBy('account_id')
            ->each(function ($files, int $accountId): void {
                $account = Account::query()->find($accountId);

                if ($account === null) {
                    return;
                }

                $files->each(fn (File $file) => new DestroyFile(account: $account, file: $file)->execute());

                LogUserAction::dispatch(
                    account: $account,
                    user: null,
                    action: UserActionEnum::OrphanedFilesSwept,
                    parameters: ['count' => $files->count()],
                )->onQueue('low');
            });
    }
}
