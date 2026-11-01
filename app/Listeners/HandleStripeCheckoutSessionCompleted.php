<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\SettleOrder;
use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Settle the order behind a checkout session Stripe says completed.
 *
 * This is what makes a purchase survive the buyer closing the tab, and it is the only way an API
 * client is ever told: nothing redirects it, so it never carries a session identifier back. The
 * session Stripe sends is used as it stands, since the signature was already checked, which saves
 * asking Stripe for what it just said.
 *
 * A delayed payment method, a SEPA debit or a bank transfer, completes the session unpaid and is
 * only paid days later, which Stripe says with `checkout.session.async_payment_succeeded`. That
 * event carries the same session, so it settles the order the same way.
 *
 * It is queued and retried so that a slow database never holds up the webhook's answer and a
 * confirmation is not dropped after one failure. Settling twice is impossible: `SettleOrder`
 * returns the order untouched when it is already paid.
 */
class HandleStripeCheckoutSessionCompleted implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookReceived $event): void
    {
        if (! in_array($event->payload['type'] ?? null, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return;
        }

        $session = $event->payload['data']['object'] ?? null;

        if (! is_array($session) || ! isset($session['id'])) {
            return;
        }

        $account = $this->accountFor($session);

        if ($account === null) {
            return;
        }

        new SettleOrder(
            account: $account,
            sessionId: (string) $session['id'],
            session: $session,
        )->execute();
    }

    /**
     * The account the session was opened for. One naming a customer we do not know, which is what a
     * deleted account looks like from Stripe, is written down and left alone.
     *
     * @param  array<string, mixed>  $session
     */
    private function accountFor(array $session): ?Account
    {
        $customerId = $session['customer'] ?? null;

        if (is_array($customerId)) {
            $customerId = $customerId['id'] ?? null;
        }

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        $account = Account::query()->where('stripe_id', $customerId)->first();

        if ($account === null) {
            Log::warning('Stripe completed a checkout session for a customer this instance does not know.', [
                'customer' => $customerId,
                'session' => $session['id'],
            ]);
        }

        return $account;
    }
}
