<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\PossessionTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Remove a document from a transaction of a possession. The transaction lets go of its reference,
 * and the file itself survives the grace period every detached file gets before it is swept up.
 */
class DetachPossessionTransactionDocument
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionTransaction $transaction,
        private readonly File $file,
    ) {}

    public function execute(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->transaction->documents()->detach($this->file);

            new DetachFile(user: $this->user, file: $this->file)->execute();
        });

        $this->log();
    }

    private function validate(): void
    {
        if ($this->transaction->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This transaction is in another account.');
        }

        if (! $this->transaction->documents()->whereKey($this->file->id)->exists()) {
            throw new ModelNotFoundException('This document is attached to another transaction.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTransactionDocumentRemoval,
            parameters: ['file' => $this->file->original_name, 'name' => $this->transaction->possession->name],
            loggable: $this->transaction,
        )->onQueue('low');
    }
}
