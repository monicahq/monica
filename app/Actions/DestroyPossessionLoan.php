<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\PossessionLoanProvenance;
use App\Jobs\LogUserAction;
use App\Models\PossessionLoan;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete one loan of a possession for good, with the provenance events it wrote. The contact stays,
 * and an active loan deleted leaves the possession in the account's hands.
 */
class DestroyPossessionLoan
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionLoan $loan,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->loan->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This loan is in another account.');
        }
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            PossessionLoanProvenance::remove($this->loan);

            $this->loan->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionLoanDeletion,
            parameters: ['name' => $this->loan->possession->name],
            loggable: $this->loan,
        )->onQueue('low');
    }
}
