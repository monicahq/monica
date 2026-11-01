<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessKindEnum;
use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Notifications\AccessUnlocked;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

/**
 * Settle the order behind a checkout session Stripe says was paid, which is what opens full access
 * bought outright.
 *
 * Two things reach here, often at the same moment, and neither may unlock twice, so the idempotence
 * is here rather than in them: the buyer coming back to the address Stripe redirects them to, and
 * Stripe's own confirmation that the session completed, which is what covers a buyer who closes the
 * tab. The order is marked paid in one conditional write, and only the run that wrote it unlocks. An
 * order already refunded or disputed is never paid again by a replay. The order is matched
 * on the session and the account together, so a session belonging to somebody else finds nothing.
 *
 * A second purchase on an account that already holds one is refused, because money was taken for
 * nothing and somebody has to look at it. Bought while a subscription is running, it stops that
 * subscription renewing and keeps the period already paid for, so access never gaps.
 */
class SettleOrder
{
    /**
     * @param  array<string, mixed>|null  $session  the session as Stripe wrote it, when the caller
     *                                              already holds it, as a webhook does. Retrieved
     *                                              from Stripe when it does not.
     */
    public function __construct(
        private readonly Account $account,
        private readonly string $sessionId,
        private readonly ?array $session = null,
    ) {}

    public function execute(): ?StripeOrder
    {
        $order = $this->order();

        if ($order === null) {
            return null;
        }

        if ($order->isSettled()) {
            return $order;
        }

        $session = $this->session ?? $this->sessionFromStripe();

        if (($session['payment_status'] ?? null) !== StripeOrder::PAID) {
            return null;
        }

        $this->validate($order);

        if (! $this->claim($order)) {
            return $order->refresh()->isSettled() ? $order : null;
        }

        $this->settle();
        $this->stopTheSubscriptionRenewing();
        $this->notify();
        $this->log($order);

        return $order;
    }

    private function order(): ?StripeOrder
    {
        return $this->account->orders()->where('stripe_id', $this->sessionId)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionFromStripe(): array
    {
        try {
            return Cashier::stripe()->checkout->sessions->retrieve($this->sessionId)->toArray();
        } catch (Exception $exception) {
            Log::warning('Stripe would not answer for a checkout session it sent a buyer back with.', [
                'session' => $this->sessionId,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function validate(StripeOrder $order): void
    {
        $anotherOrder = $this->account->orders()
            ->where('stripe_status', StripeOrder::PAID)
            ->whereKeyNot($order->id)
            ->exists();

        if ($anotherOrder) {
            throw new AccountAlreadyOwnsTheProduct('This account already holds the one time purchase.');
        }
    }

    private function claim(StripeOrder $order): bool
    {
        $claimed = StripeOrder::query()
            ->whereKey($order->id)
            ->whereNotIn('stripe_status', [StripeOrder::PAID, StripeOrder::REFUNDED, StripeOrder::DISPUTED])
            ->update(['stripe_status' => StripeOrder::PAID]);

        if ($claimed === 0) {
            return false;
        }

        $order->refresh();

        return true;
    }

    private function settle(): void
    {
        $this->account->billing_email = $this->account->stripeEmail() ?? $this->account->billing_email;
        $this->account->save();
        $this->account->unsetRelation('orders');
    }

    private function stopTheSubscriptionRenewing(): void
    {
        $subscription = $this->account->subscription();

        if ($subscription === null) {
            return;
        }

        if (! $subscription->valid()) {
            return;
        }

        if ($subscription->onGracePeriod()) {
            return;
        }

        $subscription->cancel();
    }

    private function notify(): void
    {
        $this->account->user?->notify(new AccessUnlocked(kind: AccessKindEnum::Lifetime));
    }

    private function log(StripeOrder $order): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->account->user,
            action: UserActionEnum::FullAccessPurchased,
            loggable: $order,
            source: LogSourceEnum::System,
        );
    }
}
