<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\PossessionTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Attach an uploaded invoice, receipt or deed to a transaction of a possession. The file counts
 * against the account's storage like every other upload, and the transaction holds one reference on it.
 */
class AttachPossessionTransactionDocument
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionTransaction $transaction,
        private readonly UploadedFile $upload,
    ) {}

    public function execute(): File
    {
        $this->validate();

        $file = new CreateFile(user: $this->user, upload: $this->upload, vault: $this->transaction->possession->vault)->execute();

        DB::transaction(function () use ($file): void {
            $this->transaction->documents()->attach($file);

            new AttachFile(user: $this->user, file: $file)->execute();
        });

        $this->log($file);

        return $file;
    }

    private function validate(): void
    {
        if ($this->transaction->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This transaction is in another account.');
        }
    }

    private function log(File $file): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTransactionDocumentAddition,
            parameters: ['file' => $file->original_name, 'name' => $this->transaction->possession->name],
            loggable: $this->transaction,
        )->onQueue('low');
    }
}
