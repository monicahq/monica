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

/**
 * How the instance is doing, as of the moment somebody opened the screen.
 *
 * Every figure here is counted when it is asked for. There is no cache and no
 * summary table, because an operator opening this wants to know what is true now
 * rather than what was true when a job last ran, and a dozen aggregates is a
 * cheap price for that.
 *
 * Each figure carries the period it covers, and the two that cover a window say
 * so out loud. A number on a dashboard without a period is a number somebody
 * will read as whatever they were expecting.
 */
class OverviewViewModel
{
    /**
     * The window the sign-in figure covers.
     */
    public const int SIGNED_IN_DAYS = 7;

    /**
     * The window the new account figure covers.
     */
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

    /**
     * How many people signed in over the last week. Somebody who has never signed
     * in has no date to compare, and is not one of them.
     */
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

    /**
     * How many marketplace submissions nobody has decided about. It is the one
     * figure on this screen that is a job rather than a fact, which is why it
     * leads to the queue.
     */
    public function awaitingModeration(): int
    {
        return once(fn (): int => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->count());
    }

    /**
     * How many emails left this instance, and where each of them got to.
     *
     * Every case of the enum is present, including the ones nothing landed on,
     * so the screen renders the same shape whatever the instance has done. The
     * conditions repeat the precedence the model reads off the same three
     * timestamps, because a count cannot ask a row what it thinks it is. That
     * duplication is the one risk here, and it is what
     * `it_agrees_with_what_each_row_says_about_itself` exists to catch.
     *
     * @return array<string, int>
     */
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

    /**
     * How many emails left this instance in all.
     *
     * Added up from the figures above rather than counted again, so the total
     * and its parts cannot disagree on a screen showing both.
     */
    public function emailsSentCount(): int
    {
        return array_sum($this->emailsSentByStatus());
    }

    public function isEmpty(): bool
    {
        return $this->accountCount() === 0;
    }
}
