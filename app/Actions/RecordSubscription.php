<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessKindEnum;
use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Notifications\AccessUnlocked;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Stripe\Invoice;

/**
 * Write down that a subscription started.
 *
 * Access is read from Cashier's own record of the subscription, so nothing about access is written
 * here: only the address the invoices go to, the email saying it is open, and the entry in the
 * account's journal.
 *
 * A subscription paid on an account that already holds access for good, from a checkout left open in
 * another tab, would bill every period for nothing. It is cancelled at once and its first invoice is
 * refunded. The invoice is written down before the refund, so the refund Stripe then reports is read
 * as money given back on that subscription and never on the purchase. The refund carries a key per
 * invoice, so a retry of this work refunds once.
 */
class RecordSubscription
{
    public function __construct(
        private readonly Account $account,
        private readonly Subscription $subscription,
    ) {}

    public function execute(): Account
    {
        if ($this->account->holdsAccessForGood()) {
            $this->withdraw();

            return $this->account;
        }

        $this->record();
        $this->notify();
        $this->log();

        return $this->account;
    }

    private function record(): void
    {
        $this->account->billing_email = $this->account->stripeEmail() ?? $this->account->billing_email;
        $this->account->save();
    }

    private function withdraw(): void
    {
        if (! $this->subscription->ended()) {
            $this->subscription->cancelNow();
        }

        $invoice = $this->subscription->asStripeSubscription(['latest_invoice'])->latest_invoice;

        if (! $invoice instanceof Invoice) {
            Log::warning('A subscription started on an account holding access for good, with no invoice to refund.', [
                'account' => $this->account->id,
                'subscription' => $this->subscription->stripe_id,
            ]);

            return;
        }

        new RecordPayment(account: $this->account, invoice: $invoice->toArray())->execute();

        $paymentIntent = Cashier::stripe()->invoicePayments->all([
            'invoice' => $invoice->id,
            'status' => 'paid',
            'limit' => 1,
        ])->first()?->toArray()['payment']['payment_intent'] ?? null;

        if (is_array($paymentIntent)) {
            $paymentIntent = $paymentIntent['id'] ?? null;
        }

        if (! is_string($paymentIntent) || $paymentIntent === '') {
            Log::warning('A subscription started on an account holding access for good, with no payment to refund.', [
                'account' => $this->account->id,
                'subscription' => $this->subscription->stripe_id,
                'invoice' => $invoice->id,
            ]);

            return;
        }

        Cashier::stripe()->refunds->create(
            ['payment_intent' => $paymentIntent],
            ['idempotency_key' => "refund-duplicate-subscription-{$invoice->id}"],
        );

        Log::warning('A subscription started on an account holding access for good. It was cancelled and its first invoice refunded.', [
            'account' => $this->account->id,
            'subscription' => $this->subscription->stripe_id,
            'invoice' => $invoice->id,
        ]);
    }

    private function notify(): void
    {
        $this->account->user?->notify(new AccessUnlocked(kind: AccessKindEnum::Subscription));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->account->user,
            action: UserActionEnum::SubscriptionStarted,
            loggable: $this->subscription,
            source: LogSourceEnum::System,
        );
    }
}
