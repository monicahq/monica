<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Notifications\AccountExportFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Delete the files of every export past its seven days, keeping the row marked expired, and give up on
 * an export whose run died without saying so. Once a day is enough, since the download is refused the
 * moment an export expires, before its file is gone.
 */
class PurgeExpiredAccountExports implements ShouldQueue
{
    use Queueable;

    public const int HOURS_BEFORE_GIVING_UP = 24;

    public function handle(): void
    {
        AccountExport::query()
            ->where('status', AccountExportStatusEnum::Ready)
            ->where('expires_at', '<=', now())
            ->lazyById()
            ->each(function (AccountExport $export): void {
                if (! $this->removeTheFilesOf($export)) {
                    return;
                }

                $export->status = AccountExportStatusEnum::Expired;
                $export->save();
            });

        AccountExport::query()
            ->where('status', AccountExportStatusEnum::Preparing)
            ->where('created_at', '<=', now()->subHours(self::HOURS_BEFORE_GIVING_UP))
            ->lazyById()
            ->each(function (AccountExport $export): void {
                if (! $this->removeTheFilesOf($export)) {
                    return;
                }

                $export->status = AccountExportStatusEnum::Failed;
                $export->save();

                $export->user->notify(new AccountExportFailed(
                    url: route('settings.accountImports.index'),
                ));
            });
    }

    private function removeTheFilesOf(AccountExport $export): bool
    {
        $disk = Storage::disk($export->disk);

        return $disk->delete($export->path) && $disk->deleteDirectory($export->directory());
    }
}
