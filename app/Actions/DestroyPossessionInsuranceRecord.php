<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete one insurance record of a possession for good. The history of the coverage loses that
 * policy period.
 */
class DestroyPossessionInsuranceRecord
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionInsuranceRecord $record,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->record->delete();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This insurance record is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionInsuranceRecordDeletion,
            parameters: ['name' => $this->record->possession->name],
            loggable: $this->record,
        )->onQueue('low');
    }
}
