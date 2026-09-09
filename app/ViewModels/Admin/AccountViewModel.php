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
        private readonly ?User $viewer = null,
    ) {}

    public function title(): string
    {
        return $this->user()->name ?? __('Account :id', ['id' => $this->account->id]);
    }

    /**
     * What the account is, in as many short words as apply.
     *
     * @return list<array{label: string, tone: string}>
     */
    public function badges(): array
    {
        $badges = [];

        if ($this->account->is_self_hosted) {
            $badges[] = ['label' => __('Self-hosted'), 'tone' => 'neutral'];
        }

        if ($this->account->has_lifetime_access) {
            $badges[] = ['label' => __('Lifetime access'), 'tone' => 'success'];
        }

        $state = match (true) {
            ! $this->hasUser() => __('Nobody on this account'),
            $this->userWasDeleted() => __('Person deleted'),
            ! $this->user()->is_active => __('Deactivated'),
            default => null,
        };

        if ($state !== null) {
            $badges[] = ['label' => $state, 'tone' => 'danger'];
        }

        return $badges;
    }

    public function deletionNotice(): ?string
    {
        if (! $this->userWasDeleted()) {
            return null;
        }

        return __('The person on this account was deleted on :date. The account and everything in it is still here.', [
            'date' => $this->user()->deleted_at->isoFormat('LL'),
        ]);
    }

    public function createdOn(): string
    {
        return $this->account->created_at->isoFormat('LL');
    }

    public function whereItRuns(): string
    {
        return $this->account->is_self_hosted ? __('Self-hosted') : __('Hosted by us');
    }

    /**
     * Whether the account holds the one time purchase, and when it was bought
     * when the date was kept. A self-hosted account is not asked the question.
     */
    public function lifetimeAccessLine(): string
    {
        if (! $this->isBillable()) {
            return __('Not applicable');
        }

        if (! $this->account->has_lifetime_access) {
            return __('No');
        }

        if ($this->account->purchased_at === null) {
            return __('Yes');
        }

        return __('Yes, bought on :date', ['date' => $this->account->purchased_at->isoFormat('LL')]);
    }

    public function billingAddressLine(): string
    {
        if (! $this->isBillable()) {
            return __('Not applicable');
        }

        return $this->account->billing_email ?? __('None given');
    }

    public function storageLine(): string
    {
        if (! $this->hasStorageLimit()) {
            return __(':used, no limit', ['used' => $this->storageUsed()]);
        }

        return __(':used of :limit', ['used' => $this->storageUsed(), 'limit' => $this->storageLimit()]);
    }

    public function vaultsLine(): string
    {
        return trans_choice(':count vault|:count vaults', $this->vaultCount());
    }

    public function contactsLine(): string
    {
        return trans_choice(':count contact|:count contacts', $this->contactCount());
    }

    public function storageLimitInBytes(): ?int
    {
        return $this->account->storage_limit_in_bytes;
    }

    public function hasLifetimeAccess(): bool
    {
        return $this->account->has_lifetime_access;
    }

    public function userName(): string
    {
        return $this->user()->name ?? '';
    }

    public function userEmail(): string
    {
        return $this->user()->email ?? '';
    }

    public function emailIsConfirmedLine(): string
    {
        return $this->user()?->hasVerifiedEmail() === true ? __('Yes') : __('No');
    }

    public function signInLine(): string
    {
        if ($this->user()?->usesSingleSignOn() !== true) {
            return __('With a password');
        }

        return __('Single sign-on through :provider', ['provider' => $this->user()->sso_provider]);
    }

    public function twoFactorLine(): string
    {
        return $this->user()?->usesTwoFactorAuthentication() === true ? __('On') : __('Off');
    }

    public function canSignInLine(): string
    {
        return $this->user()?->is_active === true ? __('Yes') : __('No, deactivated');
    }

    public function lastSignedInLine(): string
    {
        return $this->user()?->last_login_at?->isoFormat('LL') ?? __('Never');
    }

    public function canSignIn(): bool
    {
        return $this->user()?->is_active === true;
    }

    /**
     * Whether the operator is looking at their own account, which is what
     * stops them deactivating themselves and locking the instance out.
     */
    public function isTheViewer(): bool
    {
        return $this->viewer !== null && $this->user()?->is($this->viewer) === true;
    }

    /** Whether support may act on this account at all. */
    public function isActionable(): bool
    {
        return $this->hasUser() && ! $this->userWasDeleted();
    }

    public function deactivateUrl(): string
    {
        return route('admin.accounts.activation.destroy', ['account' => $this->account->id]);
    }

    public function reactivateUrl(): string
    {
        return route('admin.accounts.activation.create', ['account' => $this->account->id]);
    }

    public function storageLimitUrl(): string
    {
        return route('admin.accounts.storageLimit.update', ['account' => $this->account->id]);
    }

    public function withdrawLifetimeAccessUrl(): string
    {
        return route('admin.accounts.lifetimeAccess.destroy', ['account' => $this->account->id]);
    }

    public function grantLifetimeAccessUrl(): string
    {
        return route('admin.accounts.lifetimeAccess.create', ['account' => $this->account->id]);
    }

    public function recordUrl(): string
    {
        return route('admin.logs.index', ['account' => $this->account->id]);
    }

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
