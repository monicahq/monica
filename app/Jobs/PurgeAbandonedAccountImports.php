<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CancelAccountImport;
use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Discard every import read and never started within seven days, and every import whose reading
 * died without saying so, with the files decoded for it. Hand to the rollback every import whose
 * writing or rollback stopped moving a day ago, which a worker killed between two runs leaves behind.
 * Each would otherwise keep the account from starting another import.
 */
class PurgeAbandonedAccountImports implements ShouldQueue
{
    use Queueable;

    public const int DAYS_WAITING = 7;

    public const int HOURS_READING = 24;

    public const int HOURS_WRITING = 24;

    public function handle(): void
    {
        AccountImport::query()
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('status', AccountImportStatusEnum::Read)
                    ->where('updated_at', '<=', now()->subDays(self::DAYS_WAITING)))
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', AccountImportStatusEnum::Reading)
                    ->where('created_at', '<=', now()->subHours(self::HOURS_READING))))
            ->with('user')
            ->lazyById()
            ->each(fn (AccountImport $import) => new CancelAccountImport(user: $import->user, import: $import)->execute());

        AccountImport::query()
            ->whereIn('status', [AccountImportStatusEnum::Writing, AccountImportStatusEnum::RollingBack])
            ->where('updated_at', '<=', now()->subHours(self::HOURS_WRITING))
            ->lazyById()
            ->each(function (AccountImport $import): void {
                AccountImport::query()
                    ->whereKey($import->id)
                    ->where('status', AccountImportStatusEnum::Writing->value)
                    ->update(['status' => AccountImportStatusEnum::RollingBack->value]);

                RollBackAccountImportBatch::dispatch($import);
            });
    }
}
