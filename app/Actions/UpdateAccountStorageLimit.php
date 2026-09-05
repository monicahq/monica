<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;

/**
 * Change how much an account is allowed to hold, or lift the ceiling entirely.
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
