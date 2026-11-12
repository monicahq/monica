<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\PossessionValuation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Attach an uploaded certificate, listing or offer to a valuation of a possession. The file counts
 * against the account's storage like every other upload, and the valuation holds one reference on it.
 */
class AttachPossessionValuationDocument
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionValuation $valuation,
        private readonly UploadedFile $upload,
    ) {}

    public function execute(): File
    {
        $this->validate();

        $file = new CreateFile(user: $this->user, upload: $this->upload, vault: $this->valuation->possession->vault)->execute();

        DB::transaction(function () use ($file): void {
            $this->valuation->documents()->attach($file);

            new AttachFile(user: $this->user, file: $file)->execute();
        });

        $this->log($file);

        return $file;
    }

    private function validate(): void
    {
        if ($this->valuation->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This valuation is in another account.');
        }
    }

    private function log(File $file): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionValuationDocumentAddition,
            parameters: ['file' => $file->original_name, 'name' => $this->valuation->possession->name],
            loggable: $this->valuation,
        )->onQueue('low');
    }
}
