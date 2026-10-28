<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Stop an import. One that has not written anything yet is simply discarded with its file. One
 * that is writing is marked for its rollback, and the run writing it hands over to the rollback
 * between two items, so a cancelled import leaves the account as it was.
 */
class CancelAccountImport
{
    public function __construct(
        private readonly User $user,
        private readonly AccountImport $import,
    ) {}

    public function execute(): void
    {
        if ($this->import->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This import is in another account.');
        }

        $isHandedToTheRollback = DB::transaction(function (): bool {
            $status = AccountImport::query()->whereKey($this->import->id)->lockForUpdate()->firstOrFail()->status;

            if ($status === AccountImportStatusEnum::Writing) {
                AccountImport::query()
                    ->whereKey($this->import->id)
                    ->update(['status' => AccountImportStatusEnum::RollingBack->value]);

                return true;
            }

            $isStillBeforeWriting = in_array($status, [
                AccountImportStatusEnum::Reading,
                AccountImportStatusEnum::Read,
                AccountImportStatusEnum::Refused,
            ], true);

            if (! $isStillBeforeWriting) {
                throw new InvalidArgumentException(__('This import has already ended.'));
            }

            $this->import->items()->delete();
            $this->import->delete();

            return false;
        });

        if ($isHandedToTheRollback) {
            $this->import->refresh();

            return;
        }

        Storage::disk($this->import->disk)->deleteDirectory($this->import->directory());
    }
}
