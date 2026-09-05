<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DestroyFile;
use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\File;
use App\Models\RecordSnapshot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sweep up the files nothing points at any more, once they have sat orphaned for the grace period.
 * That period has to outlast the window in which a record can be restored, so restoring one still
 * finds the file it pointed at.
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
            ->whereNotIn('id', RecordSnapshot::fileIdsInUse())
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
