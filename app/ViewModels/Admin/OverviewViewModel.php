<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\EmailDeliveryStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Helpers\StorageSize;
use App\Models\Account;
use App\Models\Contact;
use App\Models\EmailSent;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use App\Models\Vault;

class OverviewViewModel
{
    public const int SIGNED_IN_DAYS = 7;

    public const int NEW_ACCOUNT_DAYS = 30;

    public function accountCount(): int
    {
        return once(fn (): int => Account::query()->count());
    }

    public function userCount(): int
    {
        return once(fn (): int => User::query()->count());
    }

    public function vaultCount(): int
    {
        return once(fn (): int => Vault::query()->count());
    }

    public function contactCount(): int
    {
        return once(fn (): int => Contact::query()->count());
    }

    public function storageUsed(): string
    {
        return StorageSize::readable($this->storageUsedInBytes());
    }

    public function storageUsedInBytes(): int
    {
        return once(fn (): int => (int) Account::query()->sum('storage_used_in_bytes'));
    }

    public function signedInRecently(): int
    {
        return once(fn (): int => User::query()
            ->where('last_login_at', '>=', now()->subDays(self::SIGNED_IN_DAYS))
            ->count());
    }

    public function accountsCreatedRecently(): int
    {
        return once(fn (): int => Account::query()
            ->where('created_at', '>=', now()->subDays(self::NEW_ACCOUNT_DAYS))
            ->count());
    }

    public function awaitingModeration(): int
    {
        return once(fn (): int => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->count());
    }

    /** @return array<string, int> */
    public function emailsSentByStatus(): array
    {
        return once(fn (): array => [
            EmailDeliveryStatusEnum::Delivered->value => EmailSent::query()
                ->whereNull('complained_at')
                ->whereNull('bounced_at')
                ->whereNotNull('delivered_at')
                ->count(),
            EmailDeliveryStatusEnum::Bounced->value => EmailSent::query()
                ->whereNull('complained_at')
                ->whereNotNull('bounced_at')
                ->count(),
            EmailDeliveryStatusEnum::Complained->value => EmailSent::query()
                ->whereNotNull('complained_at')
                ->count(),
            EmailDeliveryStatusEnum::Sent->value => EmailSent::query()
                ->whereNull('complained_at')
                ->whereNull('bounced_at')
                ->whereNull('delivered_at')
                ->count(),
        ]);
    }

    public function emailsSentCount(): int
    {
        return array_sum($this->emailsSentByStatus());
    }

    public function isEmpty(): bool
    {
        return $this->accountCount() === 0;
    }
}
