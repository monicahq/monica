<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\StorageSize;
use App\Models\Account;
use App\Models\User;

/**
 * What one account shows to whoever runs the instance: enough to answer a
 * support email, and nothing that was written inside a vault.
 *
 * The boundary is the point of this class. Vaults and contacts appear as counts
 * and never as names, and nothing here reaches a field value, a note or a file.
 * That is a promise to the people using the product rather than a default
 * somebody may relax later, so the way to break it would have to be to write new
 * code here, not to forget a condition somewhere else.
 *
 * Facts that only mean something on the hosted instance are marked as not
 * applicable on a self hosted one rather than shown empty, since empty reads as
 * missing data and prompts somebody to go looking for it.
 */
class AccountViewModel
{
    public function __construct(
        private readonly Account $account,
    ) {}

    public function account(): Account
    {
        return $this->account;
    }

    /**
     * The person on the account, deleted or not. An account outlives its owner
     * being deleted, and this screen has to render for it either way.
     */
    public function user(): ?User
    {
        return once(fn (): ?User => $this->account->userIncludingDeleted);
    }

    public function userWasDeleted(): bool
    {
        return $this->user()?->trashed() === true;
    }

    public function hasUser(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Whether the facts about billing and purchase mean anything here. Nobody
     * pays for an instance they run themselves.
     */
    public function isBillable(): bool
    {
        return ! $this->account->is_self_hosted;
    }

    public function vaultCount(): int
    {
        return once(fn (): int => $this->account->vaults()->count());
    }

    public function contactCount(): int
    {
        return once(fn (): int => $this->account->contacts()->count());
    }

    public function storageUsed(): string
    {
        return StorageSize::readable($this->account->storage_used_in_bytes);
    }

    public function hasStorageLimit(): bool
    {
        return $this->account->storage_limit_in_bytes !== null;
    }

    /**
     * The ceiling, or nothing when there is none. An account with no limit is
     * unlimited and is never zero, which is what a raw null would read as.
     */
    public function storageLimit(): ?string
    {
        if ($this->account->storage_limit_in_bytes === null) {
            return null;
        }

        return StorageSize::readable($this->account->storage_limit_in_bytes);
    }

    /**
     * Whether the account is already holding more than it is allowed, which
     * happens when somebody lowers a limit below what is used. Nothing is
     * deleted when that happens, so the screen has to be able to say it.
     */
    public function isOverStorageLimit(): bool
    {
        return $this->account->storage_limit_in_bytes !== null
            && $this->account->storage_used_in_bytes > $this->account->storage_limit_in_bytes;
    }
}
