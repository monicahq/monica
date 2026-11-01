<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\WriteAccountExport;
use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Notifications\AccountExportFailed;
use App\Notifications\AccountExportReady;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use RuntimeException;
use Throwable;

/**
 * Write the file of an export and tell the account holder whether it can be downloaded. It runs on a
 * queue for as long as the photos take, and a copy the queue starts while it runs waits for it to end. An
 * earlier export keeps its file until its own seven days are over, when the daily purge removes it.
 */
class BuildAccountExport implements ShouldQueue
{
    use Localizable;
    use Queueable;

    public const int DAYS_KEPT = 7;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public int $maxExceptions = 1;

    public function __construct(
        public AccountExport $export,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping((string) $this->export->id)->releaseAfter(60)->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(): void
    {
        $this->export->refresh();

        if ($this->export->status !== AccountExportStatusEnum::Preparing) {
            return;
        }

        $disk = Storage::disk($this->export->disk);
        $temporaryPath = "{$this->export->path}.".Str::random(16);

        try {
            $this->withLocale($this->export->user->locale ?? (string) config('app.locale'), function () use ($temporaryPath): void {
                new WriteAccountExport(export: $this->export, path: $temporaryPath)->execute();
            });

            $isMarkedReady = DB::transaction(function () use ($disk, $temporaryPath): bool {
                $status = AccountExport::query()->whereKey($this->export->id)->lockForUpdate()->firstOrFail()->status;

                if ($status !== AccountExportStatusEnum::Preparing) {
                    return false;
                }

                if (! $disk->move($temporaryPath, $this->export->path)) {
                    throw new RuntimeException('The export file could not be moved into place.');
                }

                AccountExport::query()
                    ->whereKey($this->export->id)
                    ->update([
                        'status' => AccountExportStatusEnum::Ready,
                        'size_in_bytes' => $disk->size($this->export->path),
                        'ready_at' => now(),
                        'expires_at' => now()->addDays(self::DAYS_KEPT),
                    ]);

                return true;
            });
        } finally {
            $disk->delete($temporaryPath);
        }

        if (! $isMarkedReady) {
            return;
        }

        $this->export->user->notify(new AccountExportReady(
            url: route('settings.accountImports.index'),
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $isMarkedFailed = AccountExport::query()
            ->whereKey($this->export->id)
            ->where('status', AccountExportStatusEnum::Preparing)
            ->update(['status' => AccountExportStatusEnum::Failed]) === 1;

        if (! $isMarkedFailed) {
            return;
        }

        Storage::disk($this->export->disk)->deleteDirectory($this->export->directory());

        $this->export->user->notify(new AccountExportFailed(
            url: route('settings.accountImports.index'),
        ));
    }
}
