<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessEndReasonEnum;
use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Notifications\AccessEnded;
use Illuminate\Support\Facades\Log;

/**
 * Put an account back on the free offer and tell its owner why.
 *
 * What is withdrawn depends on what the money paid for. A refunded or disputed purchase leaves its
 * order in place carrying Stripe's word for what happened, which is what lets a screen say when
 * access ended and why. A refunded or disputed renewal stops the subscription it paid for at once,
 * since the period it paid for has been given back. A subscription Stripe abandoned or that was cancelled
 * takes nothing off, because Cashier's own record of it already says it is over.
 *
 * No charge is ever translated back into a checkout session: an account holds at most one purchase,
 * so the account the charge names is enough to find the order. Money back on an account holding
 * neither a purchase nor a subscription is written to the log at error level rather than dropped,
 * since somebody has to look at money that came back from nothing. Stripe sending the same refund
 * again finds the purchase already withdrawn or the subscription already ended, and changes nothing.
 *
 * Either way the order and the payment row stay, so the history survives, and nothing is said to
 * somebody who still holds full access another way, such as through a grant.
 */
class EndAccess
{
    public function __construct(
        private readonly Account $account,
        private readonly AccessEndReasonEnum $reason,
        private readonly ?Payment $payment = null,
    ) {}

    public function execute(): Account
    {
        if (! $this->withdrawWhatTheMoneyPaidFor()) {
            return $this->account;
        }

        if ($this->stillHoldsFullAccess()) {
            return $this->account;
        }

        $this->notify();
        $this->log();

        return $this->account;
    }

    private function withdrawWhatTheMoneyPaidFor(): bool
    {
        if (! $this->moneyWasGivenBack()) {
            return true;
        }

        if ($this->paidForASubscription()) {
            $subscription = $this->account->subscription();

            if ($subscription?->ended()) {
                return false;
            }

            $subscription?->cancelNow();

            return true;
        }

        $order = $this->account->settledOrder();

        if ($order === null && $this->account->orders->whereIn('stripe_status', [StripeOrder::REFUNDED, StripeOrder::DISPUTED])->isNotEmpty()) {
            return false;
        }

        if ($order === null) {
            Log::error('Stripe gave money back on an account holding nothing to withdraw.', [
                'account' => $this->account->id,
                'reason' => $this->reason->value,
                'payment' => $this->payment?->stripe_id,
            ]);

            return true;
        }

        $withdrawn = StripeOrder::query()
            ->whereKey($order->id)
            ->where('stripe_status', StripeOrder::PAID)
            ->update(['stripe_status' => $this->reason === AccessEndReasonEnum::Disputed ? StripeOrder::DISPUTED : StripeOrder::REFUNDED]);

        $this->account->unsetRelation('orders');

        return $withdrawn === 1;
    }

    /**
     * Whether the money that was given back had paid a renewal rather than the purchase. The
     * payment says so when the caller could name one. When it could not, what the account holds
     * decides: a purchase to withdraw, or failing that the subscription the money was paying for.
     */
    private function paidForASubscription(): bool
    {
        if ($this->payment !== null) {
            return $this->payment->isForSubscription();
        }

        return $this->account->settledOrder() === null && $this->account->subscribed();
    }

    private function moneyWasGivenBack(): bool
    {
        return in_array($this->reason, [AccessEndReasonEnum::Refunded, AccessEndReasonEnum::Disputed], true);
    }

    private function stillHoldsFullAccess(): bool
    {
        $account = $this->account->refresh();

        if ($account->holdsAccessForGood()) {
            return true;
        }

        if (! $this->moneyWasGivenBack()) {
            return false;
        }

        return $account->subscribed();
    }

    private function notify(): void
    {
        $this->account->user?->notify(new AccessEnded(reason: $this->reason));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->account->user,
            action: UserActionEnum::FullAccessEnded,
            source: LogSourceEnum::System,
        );
    }
}
