<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\WriteAccountImportItem;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportStatusEnum;
use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Notifications\AccountImportEnded;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use Throwable;

/**
 * Write the next items of an import for about thirty seconds under the import's lock, then hand over
 * to the next run, or to the rollback once the import is cancelled. Only the run the import names
 * goes on, so a copy the queue started again stops instead of growing a second chain.
 */
class WriteAccountImportBatch implements ShouldQueue
{
    use Localizable;
    use Queueable;

    public const int SIZE = 50;

    public const int SECONDS = 30;

    public const int LOCK_SECONDS = 300;

    public const int RELEASE_SECONDS = 60;

    public int $timeout = 240;

    public int $maxExceptions = 3;

    public function __construct(
        public AccountImport $import,
        public ?string $run = null,
    ) {}

    public static function lockOf(AccountImport $import): Lock
    {
        return Cache::lock("account-import-{$import->id}", self::LOCK_SECONDS);
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        $lock = self::lockOf($this->import);

        if (! $lock->get()) {
            $this->release(self::RELEASE_SECONDS);

            return;
        }

        try {
            if ($this->import->refresh()->writing_run !== $this->run) {
                return;
            }

            do {
                $this->writeWhileLocked();
            } while ($this->job instanceof SyncJob && $this->import->refresh()->status === AccountImportStatusEnum::Writing);
        } finally {
            $lock->release();
        }

        match ($this->import->refresh()->status) {
            AccountImportStatusEnum::Writing => $this->dispatchTheNextRun(),
            AccountImportStatusEnum::RollingBack => RollBackAccountImportBatch::dispatch($this->import),
            default => null,
        };
    }

    public function failed(?Throwable $exception): void
    {
        AccountImport::query()
            ->whereKey($this->import->id)
            ->where('status', AccountImportStatusEnum::Writing->value)
            ->update(['status' => AccountImportStatusEnum::RollingBack->value]);

        if ($this->import->refresh()->status !== AccountImportStatusEnum::RollingBack) {
            return;
        }

        RollBackAccountImportBatch::dispatch($this->import);
    }

    private function dispatchTheNextRun(): void
    {
        $next = (string) Str::uuid();

        AccountImport::query()->whereKey($this->import->id)->update(['writing_run' => $next]);

        try {
            self::dispatch($this->import, $next);
        } catch (Throwable $exception) {
            AccountImport::query()
                ->whereKey($this->import->id)
                ->where('writing_run', $next)
                ->update(['writing_run' => $this->run]);

            throw $exception;
        }
    }

    private function writeWhileLocked(): void
    {
        if ($this->import->refresh()->status !== AccountImportStatusEnum::Writing) {
            return;
        }

        $this->withLocale($this->import->user->locale ?? (string) config('app.locale'), function (): void {
            request()->attributes->set('log_source', LogSourceEnum::Import);
            request()->attributes->set('log_source_detail', $this->import->uuid);

            try {
                $this->writeTheNextItems();
            } finally {
                request()->attributes->remove('log_source');
                request()->attributes->remove('log_source_detail');
            }
        });
    }

    private function writeTheNextItems(): void
    {
        $deadline = now()->addSeconds(self::SECONDS);

        $items = $this->import->items()
            ->whereNull('outcome')
            ->limit(self::SIZE)
            ->get();

        foreach ($items as $item) {
            if ($this->import->refresh()->status !== AccountImportStatusEnum::Writing) {
                return;
            }

            if (now()->isAfter($deadline)) {
                break;
            }

            new WriteAccountImportItem(user: $this->import->user, import: $this->import, item: $item)->execute();
        }

        $left = $this->import->items()->whereNull('outcome')->count();

        AccountImport::query()
            ->whereKey($this->import->id)
            ->update(['items_done' => max(0, $this->import->items_total - $left)]);

        if ($left === 0) {
            $this->complete();
        }
    }

    private function complete(): void
    {
        $hasCompleted = AccountImport::query()
            ->whereKey($this->import->id)
            ->where('status', AccountImportStatusEnum::Writing->value)
            ->update([
                'status' => AccountImportStatusEnum::Completed->value,
                'ended_at' => now(),
            ]) === 1;

        if (! $hasCompleted) {
            return;
        }

        $this->import->refresh();

        $imported = $this->import->items()
            ->whereNotIn('kind', AccountImportItem::KINDS_LEFT_OUT_OF_THE_REPORT)
            ->whereIn('outcome', [AccountImportOutcomeEnum::Imported->value, AccountImportOutcomeEnum::MergedIntoMe->value])
            ->count();
        $leftOut = $this->import->items()
            ->whereNotIn('kind', AccountImportItem::KINDS_LEFT_OUT_OF_THE_REPORT)
            ->where('outcome', AccountImportOutcomeEnum::LeftOut->value)
            ->count();

        Storage::disk($this->import->disk)->deleteDirectory($this->import->directory());

        LogUserAction::dispatch(
            account: $this->import->account,
            user: $this->import->user,
            action: UserActionEnum::AccountImportEnded,
            vault: $this->import->vault,
            parameters: ['count' => $imported],
            loggable: $this->import,
        );

        $this->import->user->notify(new AccountImportEnded(
            imported: $imported,
            leftOut: $leftOut,
            rolledBack: false,
            url: route('settings.accountImports.show', $this->import),
        ));
    }
}
