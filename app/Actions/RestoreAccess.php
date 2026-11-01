<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessKindEnum;
use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Notifications\AccessUnlocked;

/**
 * Put full access back when the money that had been given back comes back to us: a dispute won, or a
 * refund that failed or was called off.
 *
 * Only the one time purchase can be put back, and the order it was placed through is what comes
 * back: the same row returns to paid rather than a second one being written. A subscription was
 * stopped at Stripe the moment the money left, and Stripe does not bring a cancelled subscription
 * back, so somebody whose refunded renewal is reversed has to subscribe again. Nothing here pretends
 * otherwise.
 *
 * It says nothing and writes nothing when access was never withdrawn, so an event Stripe repeats
 * leaves one record and one email.
 */
class RestoreAccess
{
    public function __construct(
        private readonly Account $account,
        private readonly ?Payment $payment = null,
    ) {}

    public function execute(): Account
    {
        if (! $this->paidForThePurchase()) {
            return $this->account;
        }

        $order = $this->orderThatStopped();

        if ($order === null) {
            return $this->account;
        }

        $order->stripe_status = StripeOrder::PAID;
        $order->save();

        $this->account->unsetRelation('orders');

        $this->notify();
        $this->log();

        return $this->account;
    }

    /**
     * Whether the money that came back had paid for the purchase. The payment says so when the
     * caller could name one; otherwise a purchase on record is what says there is one to put back.
     */
    private function paidForThePurchase(): bool
    {
        if ($this->payment !== null) {
            return ! $this->payment->isForSubscription();
        }

        return $this->orderThatStopped() !== null;
    }

    /**
     * The order money was taken back out of, null when none was or when the one on record still
     * holds, which is what makes an event Stripe repeats leave one record and one email.
     */
    private function orderThatStopped(): ?StripeOrder
    {
        return $this->account->orders
            ->first(fn (StripeOrder $order): bool => in_array(
                $order->stripe_status,
                [StripeOrder::REFUNDED, StripeOrder::DISPUTED],
                true,
            ));
    }

    private function notify(): void
    {
        $this->account->user?->notify(new AccessUnlocked(kind: AccessKindEnum::Lifetime));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->account->user,
            action: UserActionEnum::FullAccessRestored,
            loggable: $this->payment,
            source: LogSourceEnum::System,
        );
    }
}
