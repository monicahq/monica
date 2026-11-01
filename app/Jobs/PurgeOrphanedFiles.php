<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DestroyFile;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteAFileInUse;
use App\Models\Account;
use App\Models\File;
use App\Models\RecordSnapshot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sweep up the files nothing points at any more, once they have sat orphaned for the grace period.
 * That period has to outlast the window in which a record can be restored, so restoring one still
 * finds the file it pointed at.
 *
 * The files a snapshot still names are read one account at a time and compared in memory, rather
 * than handed to the database as one `NOT IN` list: that list grows with every snapshot of the
 * instance and outruns the number of bound parameters SQLite accepts.
 */
class PurgeOrphanedFiles implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cutoff = now()->subDays((int) config('monica.orphaned_file_grace_period_in_days'));

        $orphans = File::query()
            ->where('reference_count', 0)
            ->whereNotNull('orphaned_at')
            ->where('orphaned_at', '<=', $cutoff);

        $accountIds = (clone $orphans)->distinct()->pluck('account_id');

        foreach ($accountIds as $accountId) {
            $account = Account::query()->find($accountId);

            if ($account === null) {
                continue;
            }

            $inUse = RecordSnapshot::fileIdsInUse($account);
            $count = 0;

            foreach ((clone $orphans)->where('account_id', $accountId)->lazyById() as $file) {
                if (in_array($file->id, $inUse, true)) {
                    continue;
                }

                try {
                    new DestroyFile(account: $account, file: $file)->execute();
                } catch (CannotDeleteAFileInUse) {
                    continue;
                }

                $count++;
            }

            if ($count === 0) {
                continue;
            }

            LogUserAction::dispatch(
                account: $account,
                user: null,
                action: UserActionEnum::OrphanedFilesSwept,
                parameters: ['count' => $count],
            );
        }
    }
}
