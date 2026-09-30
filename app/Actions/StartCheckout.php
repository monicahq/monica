<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OfferKindEnum;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Exceptions\ProviderUnreachable;
use App\Models\Account;
use App\Models\StripeOrder;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Checkout;

/**
 * Open a Stripe Checkout session for one of the offers, which is the only place a card is ever
 * entered: the product never receives, stores or sends card details.
 *
 * Both the screen and the API come through here, so an offer the account may not take is refused in
 * one place. Access is not touched: it moves only when Stripe's webhook confirms the payment, so a
 * buyer who abandons the page leaves nothing behind.
 */
class StartCheckout
{
    public function __construct(
        private readonly Account $account,
        private readonly string $priceId,
        private readonly ?string $currency = null,
    ) {}

    public function execute(): Checkout
    {
        $offer = $this->offer();

        try {
            return $offer['kind']->renews()
                ? $this->subscribeTo($offer['price_id'])
                : $this->buyOutright($offer['price_id']);
        } catch (Exception $exception) {
            throw new ProviderUnreachable('Stripe could not open a checkout session.', 0, $exception);
        }
    }

    /**
     * The offer this price is, refused unless it is one the account may still take: a lifetime
     * holder is offered nothing, and a subscriber only what sits above what they hold.
     *
     * @return array{price_id: string, kind: OfferKindEnum, amount: int, currency: string, prices: array<string, int>}
     */
    private function offer(): array
    {
        foreach ($this->account->availableOffers() as $offer) {
            if ($offer['price_id'] === $this->priceId) {
                return $offer;
            }
        }

        throw new AccountAlreadyOwnsTheProduct(__('This account cannot take up that plan.'));
    }

    /**
     * The purchase is written down before the buyer ever pays, keyed on the session Stripe just
     * opened, so that the return address and Stripe's own confirmation both have one row to settle.
     * The buyer comes back to a route of its own, carrying the session identifier Stripe fills in.
     *
     * A checkout the buyer walked away from is written over rather than left beside the new one, so
     * an account holds one order at a time. Nothing is deleted, because there is one row either way.
     */
    private function buyOutright(string $priceId): Checkout
    {
        $order = $this->orderToWriteOver() ?? $this->account->orders()->make();

        $checkout = $this->account->checkout([$priceId => 1], $this->options(
            route('settings.checkoutReturn.show').'?session_id={CHECKOUT_SESSION_ID}',
        ));

        $session = $checkout->asStripeCheckoutSession();

        $order->forceFill([
            'type' => StripeOrder::DEFAULT_TYPE,
            'stripe_id' => $session->id,
            'stripe_status' => $session->payment_status ?? StripeOrder::UNPAID,
            'stripe_price' => $priceId,
            'created_at' => now(),
        ])->save();

        $this->account->unsetRelation('orders');

        return $checkout;
    }

    /**
     * The account's own order that nobody paid, null when there is none to write over. A settled
     * order is never written over, and neither is one money was taken back out of, so the history
     * stands.
     */
    private function orderToWriteOver(): ?StripeOrder
    {
        $order = $this->account->orders()
            ->where('stripe_status', StripeOrder::UNPAID)
            ->latest()
            ->first();

        if ($order === null) {
            return null;
        }

        return $this->closeAtTheProvider($order) ? $order : null;
    }

    /**
     * Ask Stripe to close the session behind an abandoned order, so that the page it opened cannot
     * still be paid from a tab somebody left open.
     *
     * A session Stripe refuses to close is one it has already settled or closed itself. Its order is
     * then left exactly where it is, and the new checkout gets a row of its own, so money that
     * session may yet carry still has somewhere to land.
     */
    private function closeAtTheProvider(StripeOrder $order): bool
    {
        try {
            Cashier::stripe()->checkout->sessions->expire($order->stripe_id);

            return true;
        } catch (Exception $exception) {
            Log::warning('Stripe would not close an abandoned checkout session.', [
                'session' => $order->stripe_id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * A subscription writes no order. Cashier keeps it in its own table, and Stripe's subscription
     * events are what move it, so there would be nothing here to settle.
     */
    private function subscribeTo(string $priceId): Checkout
    {
        return $this->account->newSubscription('default', $priceId)
            ->checkout($this->options(route('settings.billing.index').'?checkout=success'));
    }

    /**
     * Stripe is told where to send the buyer back to, and asked to keep the address it collects so
     * that the tax share can be worked out from where they are. The currency is the one the reader
     * picked among those the catalogue quotes.
     *
     * @return array<string, mixed>
     */
    private function options(string $successUrl): array
    {
        $options = [
            'success_url' => $successUrl,
            'cancel_url' => route('settings.billing.index').'?checkout=cancelled',
            'customer_update' => ['address' => 'auto'],
            'managed_payments' => ['enabled' => true],
        ];

        if ($this->currency !== null) {
            $options['currency'] = $this->currency;
        }

        return $options;
    }
}
