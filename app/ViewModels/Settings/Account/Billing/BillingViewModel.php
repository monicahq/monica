<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Billing;

use App\Enums\AccessKindEnum;
use App\Enums\OfferKindEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\StorageSize;
use App\Helpers\StripeCatalogue;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

class BillingViewModel
{
    /** @var array<int, array{kind: OfferKindEnum, price: string, price_id: string, upgrade: bool}>|null */
    private ?array $offers = null;

    public function __construct(
        private readonly User $user,
        private readonly bool $returnedFromCheckout = false,
        private readonly ?string $currency = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function account(): Account
    {
        return $this->user->account;
    }

    public function isSelfHosted(): bool
    {
        return (bool) config('monica.self_hosted');
    }

    public function hasFullAccess(): bool
    {
        return $this->account()->hasFullAccess();
    }

    public function holdsLifetimeAccess(): bool
    {
        return $this->accessKind() === AccessKindEnum::Lifetime || $this->wasGranted();
    }

    /**
     * The order the screen speaks of, newest first, leaving out one nobody ever
     * paid: an abandoned checkout is not a purchase and is shown nowhere.
     */
    public function order(): ?StripeOrder
    {
        return once(fn (): ?StripeOrder => $this->account()->orders
            ->first(fn (StripeOrder $order): bool => $order->stripe_status !== StripeOrder::UNPAID));
    }

    public function isSubscribed(): bool
    {
        return $this->subscription()?->valid() === true;
    }

    public function subscription(): ?Subscription
    {
        return once(fn (): ?Subscription => $this->account()->subscription());
    }

    public function isEnding(): bool
    {
        return $this->subscription()?->onGracePeriod() === true;
    }

    public function endsAt(): ?CarbonImmutable
    {
        $endsAt = $this->subscription()?->ends_at;

        return $endsAt === null ? null : CarbonImmutable::instance($endsAt);
    }

    public function renewsAt(): ?CarbonImmutable
    {
        return once(fn (): ?CarbonImmutable => $this->account()->nextBillingDate());
    }

    public function accessKind(): ?AccessKindEnum
    {
        return once(fn (): ?AccessKindEnum => $this->account()->accessKind());
    }

    public function accessKindLabel(): string
    {
        if ($this->accessKind() === AccessKindEnum::Subscription) {
            return $this->subscriptionLabel();
        }

        return __($this->accessKind()?->label() ?? 'Free offer');
    }

    public function subscriptionLabel(): string
    {
        return __($this->account()->subscriptionOfferKind()?->label() ?? 'Subscription');
    }

    public function offersAvailable(): bool
    {
        return $this->account()->offersAvailable();
    }

    public function wasGranted(): bool
    {
        return $this->accessKind() === AccessKindEnum::Granted;
    }

    public function purchasedAt(): ?CarbonImmutable
    {
        return $this->order()?->created_at;
    }

    /**
     * Whether Stripe knows the account, which is what says its portal can be
     * opened. An account granted access has never reached a checkout, so Stripe
     * has never heard of it and there is nothing of theirs to show.
     */
    public function isKnownToStripe(): bool
    {
        return $this->account()->stripe_id !== null;
    }

    /**
     * When access bought outright stopped, null while it stands or when nothing
     * was ever bought. It is the moment the order last moved, and the only
     * thing that moves a settled order is money going back.
     */
    public function accessEndedAt(): ?CarbonImmutable
    {
        $order = $this->order();

        if ($order === null || $order->isSettled()) {
            return null;
        }

        return $order->updated_at;
    }

    /**
     * What the reader is told about access that ended, in Stripe's own terms,
     * null while it stands.
     */
    public function accessEndedReason(): ?string
    {
        $order = $this->order();

        if ($order === null || $order->isSettled()) {
            return null;
        }

        $date = $this->accessEndedAt()?->isoFormat('LL');

        if ($order->stripe_status === StripeOrder::DISPUTED) {
            return __('The payment was charged back on :date, so the account is back on the free offer. Nothing was deleted.', ['date' => $date]);
        }

        return __('The payment was refunded on :date, so the account is back on the free offer. Nothing was deleted.', ['date' => $date]);
    }

    public function billingEmail(): ?string
    {
        return $this->account()->billing_email;
    }

    public function paymentCount(): int
    {
        return once(fn (): int => $this->account()->payments()->count());
    }

    public function vaultsHeld(): string
    {
        return $this->held($this->account()->vaults()->count(), $this->account()->vaultLimit());
    }

    public function contactsHeld(): string
    {
        return $this->held($this->account()->contactCount(), $this->account()->contactLimit());
    }

    public function storageHeld(): string
    {
        $limit = $this->account()->storageLimitInBytes();
        $used = StorageSize::readable($this->account()->storage_used_in_bytes);

        return $limit === null
            ? __(':used, no ceiling', ['used' => $used])
            : __(':used of :limit', ['used' => $used, 'limit' => StorageSize::readable($limit)]);
    }

    /**
     * Whether the buyer is back from Stripe with nothing confirmed yet. Access moves only when
     * Stripe says so, which is usually seconds later, so the screen says as much rather than
     * showing the free offer as though nothing had happened.
     */
    public function isAwaitingConfirmation(): bool
    {
        return $this->returnedFromCheckout && ! $this->hasFullAccess();
    }

    /**
     * The offers this account may still take up, each priced in the currency the reader is reading
     * in. A longer subscription offered to a subscriber is marked as an upgrade, because it changes
     * the running subscription in place rather than being bought.
     *
     * @return array<int, array{kind: OfferKindEnum, price: string, price_id: string, upgrade: bool}>
     */
    public function offers(): array
    {
        return $this->offers ??= $this->buildOffers();
    }

    /** @return array<int, array{kind: OfferKindEnum, price: string, price_id: string, upgrade: bool}> */
    private function buildOffers(): array
    {
        $offers = [];

        foreach ($this->account()->availableOffers() as $offer) {
            $offers[] = [
                'kind' => $offer['kind'],
                'price' => $this->priceOf($offer),
                'price_id' => $offer['price_id'],
                'upgrade' => $offer['kind']->renews() && $this->isSubscribed(),
            ];
        }

        return $offers;
    }

    /**
     * The currencies the reader may see the offers in: every one the catalogue quotes.
     *
     * @return list<string>
     */
    public function currencies(): array
    {
        return once(fn (): array => StripeCatalogue::currencies());
    }

    /**
     * The currency the offers are shown in: the one the reader picked, else the one their language
     * suggests, else the first the catalogue quotes. Never anything the catalogue does not quote.
     */
    public function currency(): string
    {
        $currencies = $this->currencies();

        if ($this->currency !== null && in_array($this->currency, $currencies, true)) {
            return $this->currency;
        }

        $suggested = AvailableLocales::details(app()->getLocale())['currency'] ?? null;

        if (is_string($suggested) && in_array($suggested, $currencies, true)) {
            return $suggested;
        }

        return $currencies[0] ?? (string) config('cashier.currency');
    }

    /** @param  array{kind: OfferKindEnum, amount: int, currency: string, prices: array<string, int>}  $offer */
    private function priceOf(array $offer): string
    {
        $currency = $this->currency();
        $amount = $offer['prices'][$currency] ?? null;

        if ($amount === null) {
            $currency = $offer['currency'];
            $amount = $offer['amount'];
        }

        return __($offer['kind']->priceFormat(), [
            'price' => Cashier::formatAmount($amount, $currency, app()->getLocale()),
        ]);
    }

    private function held(int $used, ?int $limit): string
    {
        return $limit === null
            ? __(':used, no ceiling', ['used' => $used])
            : __(':used of :limit', ['used' => $used, 'limit' => $limit]);
    }
}
