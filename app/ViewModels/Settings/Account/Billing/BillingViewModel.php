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
    /** @var array<int, array{kind: OfferKindEnum, price: string, amount: string, price_id: string, upgrade: bool}>|null */
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

        return __($this->accessKind()?->label() ?? 'Free plan');
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
            return __('The payment was charged back on :date, so the account is back on the free plan. Nothing was deleted.', ['date' => $date]);
        }

        return __('The payment was refunded on :date, so the account is back on the free plan. Nothing was deleted.', ['date' => $date]);
    }

    public function billingEmail(): ?string
    {
        return $this->account()->billing_email;
    }

    public function paymentCount(): int
    {
        return once(fn (): int => $this->account()->payments()->count());
    }

    /**
     * What the account holds against each ceiling. The percentage is null where there is no
     * ceiling, since a bar measured against nothing would suggest a limit that does not exist.
     *
     * @return list<array{label: string, value: string, note: string, percent: ?int}>
     */
    public function usage(): array
    {
        $account = $this->account();
        $storageLimit = $account->storageLimitInBytes();
        $storageUsed = $account->storage_used_in_bytes;

        return [
            $this->counted(__('Contacts'), $account->contactCount(), $account->contactLimit()),
            $this->counted(__('Vaults'), $account->vaults()->count(), $account->vaultLimit()),
            [
                'label' => __('Storage'),
                'value' => $storageLimit === null
                    ? StorageSize::readable($storageUsed)
                    : __(':used of :limit', ['used' => StorageSize::readable($storageUsed), 'limit' => StorageSize::readable($storageLimit)]),
                'note' => match (true) {
                    $storageLimit === null => __('Unlimited'),
                    $this->hasFullAccess() => __('Files and photos'),
                    default => __('Free plan limit: :limit', ['limit' => StorageSize::readable($storageLimit)]),
                },
                'percent' => $storageLimit === null ? null : $this->percentOf($storageUsed, $storageLimit),
            ],
        ];
    }

    /**
     * The room full access gives for files and photos, as the questions and the benefits name it.
     */
    public function paidStorage(): string
    {
        return StorageSize::readable((int) config('monica.paid_storage_limit_in_bytes'));
    }

    /**
     * The three newest payments, each with what it bought. The invoices screen holds the rest.
     *
     * @return list<array{billed_at: CarbonImmutable, label: string, total: string}>
     */
    public function recentPayments(): array
    {
        return once(fn (): array => $this->buildRecentPayments());
    }

    /** @return list<array{billed_at: CarbonImmutable, label: string, total: string}> */
    private function buildRecentPayments(): array
    {
        $payments = [];

        foreach ($this->account()->payments()->limit(3)->get() as $payment) {
            $kind = $payment->kind ?? StripeCatalogue::kindOfPayment($payment);

            $payments[] = [
                'billed_at' => CarbonImmutable::instance($payment->billed_at),
                'label' => $kind === null ? __('Subscription') : __($kind->label()),
                'total' => $payment->total(),
            ];
        }

        return $payments;
    }

    /**
     * What the top of the screen says about the access this account holds, null when it has never
     * held any and nothing ended: a free account that never paid is shown the plans and nothing else.
     *
     * @return array{badge: string, tone: string, title: string, line: string}|null
     */
    public function summary(): ?array
    {
        return once(fn (): ?array => $this->buildSummary());
    }

    /** @return array{badge: string, tone: string, title: string, line: string}|null */
    private function buildSummary(): ?array
    {
        if ($this->wasGranted()) {
            return [
                'badge' => __('Granted'),
                'tone' => 'success',
                'title' => __('Full access'),
                'line' => __('Full access was granted by us, so there is no payment and no invoice to show.'),
            ];
        }

        if ($this->holdsLifetimeAccess()) {
            return [
                'badge' => __('Lifetime'),
                'tone' => 'success',
                'title' => __('Lifetime access'),
                'line' => $this->purchasedAt() === null
                    ? __('Full access for the lifetime of the service, with no renewals.')
                    : __('Paid once on :date. Full access for the lifetime of the service, with no renewals.', ['date' => $this->purchasedAt()->isoFormat('LL')]),
            ];
        }

        if ($this->isEnding()) {
            return [
                'badge' => __('Ending'),
                'tone' => 'warning',
                'title' => $this->subscriptionLabel(),
                'line' => __('Full access stays until :date. After that the account is back on the free plan, and nothing is deleted.', ['date' => $this->endsAt()?->isoFormat('LL')]),
            ];
        }

        $scheduled = $this->account()->scheduledOfferKind();

        if ($this->isSubscribed() && $scheduled !== null && $this->renewsAt() !== null) {
            return [
                'badge' => __('Active'),
                'tone' => 'success',
                'title' => $this->subscriptionLabel(),
                'line' => __('Full access. :plan from :date, the day its price is first billed.', [
                    'plan' => __($scheduled->label()),
                    'date' => $this->renewsAt()->isoFormat('LL'),
                ]),
            ];
        }

        if ($this->isSubscribed()) {
            return [
                'badge' => __('Active'),
                'tone' => 'success',
                'title' => $this->subscriptionLabel(),
                'line' => $this->renewsAt() === null
                    ? __('Full access for as long as the subscription runs.')
                    : __('Full access, renewing on :date.', ['date' => $this->renewsAt()->isoFormat('LL')]),
            ];
        }

        if ($this->accessEndedReason() !== null) {
            return [
                'badge' => __('Ended'),
                'tone' => 'danger',
                'title' => __('Free plan'),
                'line' => $this->accessEndedReason(),
            ];
        }

        if ($this->paymentCount() === 0) {
            return null;
        }

        return [
            'badge' => __('Ended'),
            'tone' => 'neutral',
            'title' => __('Free plan'),
            'line' => $this->endsAt() === null
                ? __('Nothing you added was deleted, and the limits of the free plan apply again.')
                : __('Your subscription ended on :date. Nothing you added was deleted, and the limits of the free plan apply again.', ['date' => $this->endsAt()->isoFormat('LL')]),
        ];
    }

    /**
     * Twelve monthly payments against one yearly payment, in the currency the plans are shown in,
     * null when either price is missing, when they are quoted in two currencies, or when yearly
     * saves nothing.
     */
    public function yearlySaving(): ?string
    {
        $amounts = [];

        foreach (StripeCatalogue::offers() as $offer) {
            $amounts[$offer['kind']->value] = $this->amountOf($offer);
        }

        $monthly = $amounts[OfferKindEnum::Monthly->value] ?? null;
        $yearly = $amounts[OfferKindEnum::Yearly->value] ?? null;

        if ($monthly === null || $yearly === null) {
            return null;
        }

        if ($monthly['currency'] !== $yearly['currency']) {
            return null;
        }

        $saving = 12 * $monthly['amount'] - $yearly['amount'];

        if ($saving <= 0) {
            return null;
        }

        return Cashier::formatAmount($saving, $monthly['currency'], app()->getLocale());
    }

    /**
     * Whether the buyer is back from Stripe with nothing confirmed yet. Access moves only when
     * Stripe says so, which is usually seconds later, so the screen says as much rather than
     * showing the free plan as though nothing had happened.
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
     * @return array<int, array{kind: OfferKindEnum, price: string, amount: string, price_id: string, upgrade: bool}>
     */
    public function offers(): array
    {
        return $this->offers ??= $this->buildOffers();
    }

    /** @return array<int, array{kind: OfferKindEnum, price: string, amount: string, price_id: string, upgrade: bool}> */
    private function buildOffers(): array
    {
        $offers = [];

        foreach ($this->account()->availableOffers() as $offer) {
            $offers[] = [
                'kind' => $offer['kind'],
                'price' => $this->priceOf($offer),
                'amount' => $this->formattedAmountOf($offer),
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
        return __($offer['kind']->priceFormat(), [
            'price' => $this->formattedAmountOf($offer),
        ]);
    }

    /** @param  array{kind: OfferKindEnum, amount: int, currency: string, prices: array<string, int>}  $offer */
    private function formattedAmountOf(array $offer): string
    {
        $amount = $this->amountOf($offer);

        return Cashier::formatAmount($amount['amount'], $amount['currency'], app()->getLocale());
    }

    /**
     * The amount of an offer in the currency the plans are shown in, else in the one Stripe quotes.
     *
     * @param  array{kind: OfferKindEnum, amount: int, currency: string, prices: array<string, int>}  $offer
     * @return array{amount: int, currency: string}
     */
    private function amountOf(array $offer): array
    {
        $currency = $this->currency();
        $amount = $offer['prices'][$currency] ?? null;

        if ($amount === null) {
            return ['amount' => $offer['amount'], 'currency' => $offer['currency']];
        }

        return ['amount' => $amount, 'currency' => $currency];
    }

    /** @return array{label: string, value: string, note: string, percent: ?int} */
    private function counted(string $label, int $used, ?int $limit): array
    {
        if ($limit === null) {
            return ['label' => $label, 'value' => (string) $used, 'note' => __('Unlimited'), 'percent' => null];
        }

        return [
            'label' => $label,
            'value' => __(':used of :limit', ['used' => $used, 'limit' => $limit]),
            'note' => __('Free plan limit: :limit', ['limit' => $limit]),
            'percent' => $this->percentOf($used, $limit),
        ];
    }

    private function percentOf(int $used, int $limit): int
    {
        if ($limit === 0) {
            return 100;
        }

        return min(100, (int) round($used / $limit * 100));
    }
}
