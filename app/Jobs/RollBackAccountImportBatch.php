<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RollBackAccountImport;
use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\Vault;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use Throwable;

/**
 * Remove what an import wrote after it was cancelled or failed, under the lock of the run writing it.
 * The rollback works in one transaction, so one that keeps failing leaves everything in place.
 */
class RollBackAccountImportBatch implements ShouldQueue
{
    use Localizable;
    use Queueable;

    public int $timeout = 3600;

    public bool $failOnTimeout = true;

    public int $maxExceptions = 3;

    public function __construct(
        public AccountImport $import,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        $lock = WriteAccountImportBatch::lockOf($this->import);

        if (! $lock->get()) {
            $this->release(WriteAccountImportBatch::RELEASE_SECONDS);

            return;
        }

        try {
            if ($this->import->refresh()->status !== AccountImportStatusEnum::RollingBack) {
                return;
            }

            $this->withLocale($this->import->user->locale ?? (string) config('app.locale'), function (): void {
                new RollBackAccountImport(import: $this->import)->execute();
            });
        } finally {
            $lock->release();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $hasFailed = AccountImport::query()
            ->whereKey($this->import->id)
            ->where('status', AccountImportStatusEnum::RollingBack->value)
            ->update([
                'status' => AccountImportStatusEnum::RollbackFailed->value,
                'ended_at' => now(),
            ]) === 1;

        if (! $hasFailed) {
            return;
        }

        Storage::disk($this->import->disk)->deleteDirectory($this->import->directory());

        foreach (Vault::query()->whereKey($this->import->vaultIds())->get() as $vault) {
            DetectDuplicateContacts::dispatch($vault)->onQueue('low');
        }
    }
}
