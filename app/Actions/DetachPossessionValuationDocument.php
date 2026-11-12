<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\PossessionValuation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Remove a document from a valuation of a possession. The valuation lets go of its reference,
 * and the file itself survives the grace period every detached file gets before it is swept up.
 */
class DetachPossessionValuationDocument
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionValuation $valuation,
        private readonly File $file,
    ) {}

    public function execute(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->valuation->documents()->detach($this->file);

            new DetachFile(user: $this->user, file: $this->file)->execute();
        });

        $this->log();
    }

    private function validate(): void
    {
        if ($this->valuation->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This valuation is in another account.');
        }

        if (! $this->valuation->documents()->whereKey($this->file->id)->exists()) {
            throw new ModelNotFoundException('This document is attached to another valuation.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionValuationDocumentRemoval,
            parameters: ['file' => $this->file->original_name, 'name' => $this->valuation->possession->name],
            loggable: $this->valuation,
        )->onQueue('low');
    }
}
