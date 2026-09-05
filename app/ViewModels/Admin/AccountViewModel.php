<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\StorageSize;
use App\Models\Account;
use App\Models\User;

class AccountViewModel
{
    public function __construct(
        private readonly Account $account,
    ) {}

    public function account(): Account
    {
        return $this->account;
    }

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

    public function storageLimit(): ?string
    {
        if ($this->account->storage_limit_in_bytes === null) {
            return null;
        }

        return StorageSize::readable($this->account->storage_limit_in_bytes);
    }

    public function isOverStorageLimit(): bool
    {
        return $this->account->storage_limit_in_bytes !== null
            && $this->account->storage_used_in_bytes > $this->account->storage_limit_in_bytes;
    }
}
