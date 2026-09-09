<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\StorageSize;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Subscription;

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

        if ($this->isSelfHosted()) {
            $badges[] = ['label' => __('Self-hosted'), 'tone' => 'neutral'];
        }

        if ($this->holdsAccessForGood()) {
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
        return $this->isSelfHosted() ? __('Self-hosted') : __('Hosted by us');
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

    public function isSelfHosted(): bool
    {
        return (bool) config('monica.self_hosted');
    }

    public function isBillable(): bool
    {
        return ! $this->isSelfHosted();
    }

    /**
     * Whether the account holds full access that does not renew, which is what
     * the badge and the withdrawal button read.
     */
    public function holdsAccessForGood(): bool
    {
        return $this->account()->holdsAccessForGood();
    }

    /**
     * How that access is held, in one sentence: bought on a day, granted on a
     * day, or not held at all.
     */
    public function accessForGoodLabel(): string
    {
        $order = $this->account()->settledOrder();

        if ($order !== null) {
            return __('Yes, bought on :date', ['date' => $order->created_at->isoFormat('LL')]);
        }

        $grantedAt = $this->account()->access_granted_at;

        if ($grantedAt !== null) {
            return __('Yes, granted on :date', ['date' => $grantedAt->isoFormat('LL')]);
        }

        return __('No');
    }

    /**
     * How full access is held, in the words the owner's own screen uses, and the
     * free offer when it is not held at all.
     */
    public function accessKindLabel(): string
    {
        return $this->account->accessKind()?->label() ?? __('Free offer');
    }

    /**
     * Everything the billing panel shows, in the order it shows it: what access is held and how it
     * is held, then the order behind a purchase and the subscription behind a renewal, each left
     * out when there is none.
     *
     * @return list<array{label: string, value: string}>
     */
    public function billingFacts(): array
    {
        $facts = [
            ['label' => __('Access'), 'value' => $this->accessKindLabel()],
            ['label' => __('Lifetime access'), 'value' => $this->accessForGoodLabel()],
        ];

        $order = $this->order();

        if ($order !== null) {
            $facts[] = ['label' => __('Order'), 'value' => $order->offerKind()?->label() ?? $order->stripe_price];
            $facts[] = ['label' => __('State at Stripe'), 'value' => $order->stripe_status];
        }

        $subscription = $this->subscription();

        if ($subscription === null) {
            return $facts;
        }

        $facts[] = ['label' => __('Subscription'), 'value' => $this->subscriptionOfferLabel() ?? (string) $subscription->stripe_price];

        if ($this->renewsAt() !== null) {
            $facts[] = ['label' => __('Renews on'), 'value' => $this->renewsAt()->isoFormat('LL')];
        }

        if ($this->endsAt() !== null) {
            $facts[] = ['label' => __('Ends on'), 'value' => $this->endsAt()->isoFormat('LL')];
        }

        return $facts;
    }

    /**
     * The order the screen speaks of, newest first, leaving out one nobody paid,
     * the same way the owner's screen leaves it out.
     */
    public function order(): ?StripeOrder
    {
        return once(fn (): ?StripeOrder => $this->account->orders
            ->first(fn (StripeOrder $order): bool => $order->stripe_status !== StripeOrder::UNPAID));
    }

    public function subscription(): ?Subscription
    {
        return once(fn (): ?Subscription => $this->account->subscription());
    }

    /**
     * Which offer the running subscription is on, null when nothing runs or when
     * the catalogue no longer knows the price it bills.
     */
    public function subscriptionOfferLabel(): ?string
    {
        return $this->account->subscriptionOfferKind()?->label();
    }

    public function renewsAt(): ?CarbonImmutable
    {
        return $this->account->nextBillingDate();
    }

    public function endsAt(): ?CarbonImmutable
    {
        return $this->subscription()?->onGracePeriod() === true ? $this->subscription()->ends_at : null;
    }

    /**
     * The customer as Stripe's own dashboard shows them, null for an account
     * Stripe has never heard of, which is what a grant and the free offer look
     * like. Test keys point at the test dashboard, so an operator never lands on
     * the live customer while working against test data.
     */
    public function stripeCustomerUrl(): ?string
    {
        $customerId = $this->account->stripe_id;

        if ($customerId === null) {
            return null;
        }

        $mode = str_contains((string) config('cashier.secret'), '_test_') ? 'test/' : '';

        return "https://dashboard.stripe.com/{$mode}customers/{$customerId}";
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
        return $this->account->storageLimitInBytes() !== null;
    }

    public function storageLimit(): ?string
    {
        $limit = $this->account->storageLimitInBytes();

        if ($limit === null) {
            return null;
        }

        return StorageSize::readable($limit);
    }

    public function isOverStorageLimit(): bool
    {
        $limit = $this->account->storageLimitInBytes();

        return $limit !== null && $this->account->storage_used_in_bytes > $limit;
    }
}
