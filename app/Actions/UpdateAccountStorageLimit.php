<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;

/**
 * Change how much an account is allowed to hold, or lift the ceiling entirely.
 *
 * A limit below what the account already holds is allowed, and deletes nothing.
 * The check that refuses an upload lives where uploads happen, so lowering the
 * limit stops the next file and leaves every file already there exactly as it
 * is. Deleting somebody's documents to make a number fit is not a decision this
 * product takes on their behalf.
 *
 * A null limit means no ceiling.
 */
class UpdateAccountStorageLimit
{
    public function __construct(
        private readonly Account $account,
        private readonly User $administrator,
        private readonly ?int $limitInBytes,
    ) {}

    public function execute(): Account
    {
        $previous = $this->account->storage_limit_in_bytes;

        $this->account->storage_limit_in_bytes = $this->limitInBytes;
        $this->account->save();

        $this->log($previous);

        return $this->account;
    }

    private function log(?int $previous): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::AccountStorageLimitUpdated,
            account: $this->account,
            parameters: [
                'name' => $this->account->ownerName(),
                'from' => $previous,
                'to' => $this->limitInBytes,
            ],
            subject: $this->account,
        )->onQueue('low');
    }
}
