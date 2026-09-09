<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\RecordPayment;
use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Record an invoice Stripe says was paid, so that a screen can show what was charged without
 * waiting on Stripe.
 *
 * It records and nothing else. Access is not opened here: a renewal flows from the subscription
 * itself, and the one time purchase flows from its order, which the checkout session settles. An
 * invoice Stripe happens to raise for a purchase is therefore history and not a key.
 *
 * It is queued and retried so that a slow database never holds up the webhook's answer and a
 * confirmation is not dropped after one failure. Handling is idempotent on the invoice, since
 * `RecordPayment` writes one row per invoice however often Stripe repeats itself.
 */
class HandleStripeInvoicePaid implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'invoice.paid') {
            return;
        }

        $invoice = $event->payload['data']['object'] ?? null;

        if (! is_array($invoice) || ! isset($invoice['id'])) {
            return;
        }

        $account = $this->accountFor($invoice);

        if ($account === null) {
            return;
        }

        new RecordPayment(account: $account, invoice: $invoice)->execute();
    }

    /**
     * The account Stripe raised the invoice against. One naming a customer we do not know, which is
     * what a deleted account looks like from Stripe, is written down and left alone.
     *
     * @param  array<string, mixed>  $invoice
     */
    private function accountFor(array $invoice): ?Account
    {
        $customerId = $invoice['customer'] ?? null;

        if (is_array($customerId)) {
            $customerId = $customerId['id'] ?? null;
        }

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        $account = Account::query()->where('stripe_id', $customerId)->first();

        if ($account === null) {
            Log::warning('Stripe confirmed a payment for a customer this instance does not know.', [
                'customer' => $customerId,
                'invoice' => $invoice['id'],
            ]);
        }

        return $account;
    }
}
