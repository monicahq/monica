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
use Carbon\CarbonImmutable;

class OverviewViewModel
{
    public const int SIGNED_IN_DAYS = 7;

    public const int NEW_ACCOUNT_DAYS = 30;

    public function __construct(
        private readonly User $user,
    ) {}

    public function countedAtMoment(): CarbonImmutable
    {
        return once(fn (): CarbonImmutable => CarbonImmutable::now($this->user->timezone));
    }

    public function countedAt(): string
    {
        return $this->user->time_format->format($this->countedAtMoment());
    }

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

    public function moderationSentence(): string
    {
        if ($this->awaitingModeration() === 0) {
            return __('Nothing in the moderation queue right now.');
        }

        return trans_choice(':count marketplace submission is waiting for a decision.|:count marketplace submissions are waiting for a decision.', $this->awaitingModeration());
    }

    public function signedInLabel(): string
    {
        return trans_choice('signed in, last :count day|signed in, last :count days', self::SIGNED_IN_DAYS);
    }

    public function newAccountsLabel(): string
    {
        return trans_choice('signed up, last :count day|signed up, last :count days', self::NEW_ACCOUNT_DAYS);
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

    /** @return array<string, float> */
    public function emailShares(): array
    {
        $total = $this->emailsSentCount();

        return array_map(
            fn (int $count): float => $total === 0 ? 0.0 : round($count / $total * 100, 1),
            $this->emailsSentByStatus(),
        );
    }

    public function emailSummary(): string
    {
        if ($this->emailsSentCount() === 0) {
            return __('No email sent yet.');
        }

        $counts = $this->emailsSentByStatus();

        return trans_choice(':count email sent: :delivered delivered, :bounced bounced, :complained marked as spam, :sent on its way.|:count emails sent: :delivered delivered, :bounced bounced, :complained marked as spam, :sent on its way.', $this->emailsSentCount(), [
            'delivered' => $counts[EmailDeliveryStatusEnum::Delivered->value],
            'bounced' => $counts[EmailDeliveryStatusEnum::Bounced->value],
            'complained' => $counts[EmailDeliveryStatusEnum::Complained->value],
            'sent' => $counts[EmailDeliveryStatusEnum::Sent->value],
        ]);
    }

    public function isEmpty(): bool
    {
        return $this->accountCount() === 0;
    }
}
