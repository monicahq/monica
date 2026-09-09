<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Account;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Cashier;

/**
 * Write down an invoice Stripe says was paid.
 *
 * Stripe keeps the invoices and answers them from its API, which no screen can afford to wait on,
 * so what a screen shows is copied here. The row is keyed on the invoice, so the same confirmation
 * arriving twice updates one row rather than adding a second, and the caller can tell the two apart
 * by whether the row it gets back was recently created.
 *
 * What the payment unlocked is read off the invoice itself, never off the tag its price carries: an
 * invoice raised for no subscription is the one time purchase, and a mislabelled price therefore
 * cannot grant access for good.
 */
class RecordPayment
{
    private Payment $payment;

    /** @param  array<string, mixed>  $invoice */
    public function __construct(
        private readonly Account $account,
        private readonly array $invoice,
    ) {}

    public function execute(): Payment
    {
        $this->record();

        return $this->payment;
    }

    private function record(): void
    {
        $subscriptionId = $this->subscriptionId();

        $this->payment = Payment::query()->updateOrCreate([
            'stripe_id' => (string) $this->invoice['id'],
        ], [
            'account_id' => $this->account->id,
            'stripe_subscription_id' => $subscriptionId,
            'kind' => $this->kind($subscriptionId),
            'status' => (string) ($this->invoice['status'] ?? 'paid'),
            'total' => (int) ($this->invoice['total'] ?? 0),
            'tax' => $this->tax(),
            'currency' => (string) ($this->invoice['currency'] ?? config('cashier.currency')),
            'invoice_number' => $this->invoice['number'] ?? null,
            'hosted_invoice_url' => $this->invoice['hosted_invoice_url'] ?? null,
            'invoice_pdf_url' => $this->invoice['invoice_pdf'] ?? null,
            'billed_at' => $this->billedAt(),
        ]);
    }

    /**
     * The subscription the invoice was raised for, which since the 2025 API lives under the
     * invoice's parent rather than beside it. Null is the one time purchase.
     */
    private function subscriptionId(): ?string
    {
        $parent = $this->invoice['parent'] ?? null;

        if (! is_array($parent)) {
            return null;
        }

        if (($parent['type'] ?? null) !== 'subscription_details') {
            return null;
        }

        $subscription = $parent['subscription_details']['subscription'] ?? null;

        if (is_array($subscription)) {
            $subscription = $subscription['id'] ?? null;
        }

        return $subscription === null ? null : (string) $subscription;
    }

    /**
     * Which offer was paid for, kept so the history survives a price being retagged or withdrawn.
     * Null when a subscription was charged and the catalogue no longer knows its price.
     */
    private function kind(?string $subscriptionId): ?OfferKindEnum
    {
        if ($subscriptionId === null) {
            return OfferKindEnum::Lifetime;
        }

        $subscription = Cashier::$subscriptionModel::query()->where('stripe_id', $subscriptionId)->first();

        return $subscription === null ? null : StripeCatalogue::kindOfSubscription($subscription);
    }

    /**
     * The tax share of the total. Stripe stopped answering one figure for it and now lists the
     * taxes it worked out, so the share is their sum.
     */
    private function tax(): int
    {
        $taxes = $this->invoice['total_taxes'] ?? [];

        if (! is_array($taxes)) {
            return 0;
        }

        return array_sum(array_map(fn (mixed $tax): int => (int) ($tax['amount'] ?? 0), $taxes));
    }

    private function billedAt(): CarbonImmutable
    {
        $paidAt = $this->invoice['status_transitions']['paid_at'] ?? $this->invoice['created'] ?? null;

        return $paidAt === null ? CarbonImmutable::now() : CarbonImmutable::createFromTimestamp((int) $paidAt);
    }
}
