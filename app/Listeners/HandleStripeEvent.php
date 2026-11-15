<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\EndAccess;
use App\Actions\RecordPayment;
use App\Actions\RecordSubscription;
use App\Actions\RestoreAccess;
use App\Actions\SettleOrder;
use App\Enums\AccessEndReasonEnum;
use App\Helpers\StripeCharge;
use App\Models\Account;
use App\Models\StripeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription;
use LogicException;

/**
 * Act once on every Stripe event the application cares about.
 *
 * Stripe may send an event twice, or late. Each event is acted on once: its id is written in the
 * same transaction as what it changes, so a second delivery finds the id and changes nothing, and a
 * failed attempt leaves no id behind for the retry to trip over. An event that led to nothing, such
 * as one naming a customer the instance does not know yet, keeps no id either, so sending it again
 * from Stripe still works. An event about a subscription the
 * account no longer runs is dropped, so a late end of an old subscription never tells somebody
 * holding a new one that their access ended.
 *
 * Cashier announces every event when it arrives, and again once its own handler has run, for the
 * few types it handles itself. A subscription event is acted on in the second announcement, since
 * the subscription row is only there by then; every other type in the first.
 *
 * It is queued and retried so that a slow database never holds up the webhook's answer and an event
 * is not dropped after one failure.
 */
class HandleStripeEvent implements ShouldQueue
{
    /** @var array<string, class-string> */
    public const array ROUTES = [
        'checkout.session.completed' => WebhookReceived::class,
        'checkout.session.async_payment_succeeded' => WebhookReceived::class,
        'invoice.paid' => WebhookReceived::class,
        'charge.refunded' => WebhookReceived::class,
        'charge.dispute.created' => WebhookReceived::class,
        'charge.refund.updated' => WebhookReceived::class,
        'charge.dispute.closed' => WebhookReceived::class,
        'customer.subscription.created' => WebhookHandled::class,
        'customer.subscription.updated' => WebhookHandled::class,
        'customer.subscription.deleted' => WebhookHandled::class,
    ];

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookReceived|WebhookHandled $event): void
    {
        $id = $event->payload['id'] ?? null;
        $type = $event->payload['type'] ?? null;
        $object = $event->payload['data']['object'] ?? null;

        if (! is_string($type) || (self::ROUTES[$type] ?? null) !== $event::class) {
            return;
        }

        if (! is_string($id) || $id === '' || ! is_array($object)) {
            return;
        }

        DB::transaction(function () use ($id, $type, $object): void {
            $recorded = StripeEvent::query()->insertOrIgnore([
                'stripe_id' => $id,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($recorded === 0) {
                return;
            }

            $acted = match ($type) {
                'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->settleOrder($object),
                'invoice.paid' => $this->recordPayment($object),
                'charge.refunded', 'charge.dispute.created' => $this->endAccessForMoneyGivenBack($type, $object),
                'charge.refund.updated', 'charge.dispute.closed' => $this->restoreAccess($type, $object),
                'customer.subscription.created' => $this->recordSubscription($object),
                'customer.subscription.updated' => $this->forgetScheduledPrice($object),
                'customer.subscription.deleted' => $this->endAccessForEndedSubscription($object),
                default => throw new LogicException("The route for {$type} names nothing to do."),
            };

            if (! $acted) {
                StripeEvent::query()->where('stripe_id', $id)->delete();
            }
        });
    }

    /** @param  array<string, mixed>  $session */
    private function settleOrder(array $session): bool
    {
        if (! isset($session['id'])) {
            return false;
        }

        $account = $this->accountForCustomer($session, 'Stripe completed a checkout session for a customer this instance does not know.');

        if ($account === null) {
            return false;
        }

        new SettleOrder(
            account: $account,
            sessionId: (string) $session['id'],
            session: $session,
        )->execute();

        return true;
    }

    /** @param  array<string, mixed>  $invoice */
    private function recordPayment(array $invoice): bool
    {
        if (! isset($invoice['id'])) {
            return false;
        }

        $account = $this->accountForCustomer($invoice, 'Stripe confirmed a payment for a customer this instance does not know.');

        if ($account === null) {
            return false;
        }

        new RecordPayment(account: $account, invoice: $invoice)->execute();

        return true;
    }

    /**
     * Withdraw full access when the whole amount that paid for it is refunded or disputed. A partial
     * refund is a gesture rather than an undoing. Money back on a renewal of a subscription the
     * account no longer runs leaves the one it runs now alone.
     *
     * @param  array<string, mixed>  $object
     */
    private function endAccessForMoneyGivenBack(string $type, array $object): bool
    {
        $charge = $type === 'charge.refunded' ? $object : StripeCharge::behind($object['charge'] ?? null);

        if ($charge === null) {
            return false;
        }

        if ($type === 'charge.refunded' && ! $this->wasRefundedInFull($charge)) {
            return false;
        }

        $account = StripeCharge::accountFor($charge);

        if ($account === null) {
            return false;
        }

        $payment = StripeCharge::paymentFor($charge);

        if ($payment !== null && $payment->isForSubscription() && $payment->stripe_subscription_id !== $account->subscription()?->stripe_id) {
            return false;
        }

        new EndAccess(
            account: $account,
            reason: $type === 'charge.refunded' ? AccessEndReasonEnum::Refunded : AccessEndReasonEnum::Disputed,
            payment: $payment,
        )->execute();

        return true;
    }

    /**
     * Put full access back when a refund fails or is called off, or a dispute is won. Every other
     * step of a refund or a dispute leaves access as it is.
     *
     * @param  array<string, mixed>  $object
     */
    private function restoreAccess(string $type, array $object): bool
    {
        $moneyStayed = $type === 'charge.refund.updated'
            ? in_array($object['status'] ?? null, ['failed', 'canceled'], true)
            : ($object['status'] ?? null) === 'won';

        if (! $moneyStayed) {
            return false;
        }

        $charge = StripeCharge::behind($object['charge'] ?? null);

        if ($charge === null) {
            return false;
        }

        $account = StripeCharge::accountFor($charge);

        if ($account === null) {
            return false;
        }

        new RestoreAccess(account: $account, payment: StripeCharge::paymentFor($charge))->execute();

        return true;
    }

    /** @param  array<string, mixed>  $object */
    private function recordSubscription(array $object): bool
    {
        $subscription = $this->subscriptionFor($object);

        if ($subscription === null || ! $subscription->owner instanceof Account) {
            return false;
        }

        new RecordSubscription(account: $subscription->owner, subscription: $subscription)->execute();

        return true;
    }

    /**
     * Forget a move to a longer subscription once it has happened, or once the schedule carrying it
     * is gone. Cashier has already written the new price by then; this only clears what the screen
     * shows as still to come.
     *
     * @param  array<string, mixed>  $object
     */
    private function forgetScheduledPrice(array $object): bool
    {
        $subscription = $this->currentSubscriptionFor($object);

        if ($subscription === null || $subscription->scheduled_stripe_price === null) {
            return false;
        }

        $scheduleIsGone = ($object['schedule'] ?? null) === null;
        $moveHappened = ($object['items']['data'][0]['price']['id'] ?? null) === $subscription->scheduled_stripe_price;

        if (! $scheduleIsGone && ! $moveHappened) {
            return false;
        }

        $subscription->forceFill(['scheduled_stripe_price' => null])->save();

        return true;
    }

    /**
     * Put an account back on the free offer once Stripe has given up on its subscription, or once
     * the period a cancelled one had already paid for is over. Why it ended is in the payload rather
     * than in the local row: a card refused once must not end anything, only Stripe abandoning the
     * subscription does.
     *
     * @param  array<string, mixed>  $object
     */
    private function endAccessForEndedSubscription(array $object): bool
    {
        $subscription = $this->currentSubscriptionFor($object);

        if ($subscription === null) {
            return false;
        }

        $reason = ($object['cancellation_details']['reason'] ?? null) === 'payment_failed'
            ? AccessEndReasonEnum::PaymentFailed
            : AccessEndReasonEnum::Cancelled;

        new EndAccess(account: $subscription->owner, reason: $reason)->execute();

        return true;
    }

    /**
     * The subscription the event names, null when it is unknown or when its account now runs another
     * one, since an event about a subscription that has been replaced is stale.
     *
     * @param  array<string, mixed>  $object
     */
    private function currentSubscriptionFor(array $object): ?Subscription
    {
        $subscription = $this->subscriptionFor($object);

        if ($subscription === null || ! $subscription->owner instanceof Account) {
            return null;
        }

        if ($subscription->owner->subscription()?->stripe_id !== $subscription->stripe_id) {
            return null;
        }

        return $subscription;
    }

    /** @param  array<string, mixed>  $object */
    private function subscriptionFor(array $object): ?Subscription
    {
        if (! is_string($object['id'] ?? null) || $object['id'] === '') {
            return null;
        }

        return Cashier::$subscriptionModel::query()->where('stripe_id', $object['id'])->first();
    }

    /**
     * The account a session or an invoice was raised against. One naming a customer we do not know,
     * which is what a deleted account looks like from Stripe, is written down and left alone.
     *
     * @param  array<string, mixed>  $object
     */
    private function accountForCustomer(array $object, string $unknownCustomer): ?Account
    {
        $customerId = $object['customer'] ?? null;

        if (is_array($customerId)) {
            $customerId = $customerId['id'] ?? null;
        }

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        $account = Account::query()->where('stripe_id', $customerId)->first();

        if ($account === null) {
            Log::warning($unknownCustomer, [
                'customer' => $customerId,
                'object' => $object['id'] ?? null,
            ]);
        }

        return $account;
    }

    /** @param  array<string, mixed>  $charge */
    private function wasRefundedInFull(array $charge): bool
    {
        $amount = (int) ($charge['amount'] ?? 0);

        if ($amount === 0) {
            return false;
        }

        return (int) ($charge['amount_refunded'] ?? 0) >= $amount;
    }
}
