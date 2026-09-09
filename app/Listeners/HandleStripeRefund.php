<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\EndAccess;
use App\Enums\AccessEndReasonEnum;
use App\Helpers\StripeCharge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Withdraw full access when the money that paid for it is given back, whether the owner asked
 * Stripe for a refund or asked their bank to dispute the charge.
 *
 * Only the whole amount ends access. A partial refund is a gesture rather than an undoing, and the
 * payment record stays either way, so the history of what was paid survives. A dispute later won, or
 * a refund that then fails, is put back by the two listeners next to this one.
 */
class HandleStripeRefund implements ShouldQueue
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;

        if (! in_array($type, ['charge.refunded', 'charge.dispute.created'], true)) {
            return;
        }

        $object = $event->payload['data']['object'] ?? [];

        if (! is_array($object)) {
            return;
        }

        $charge = $type === 'charge.refunded' ? $object : StripeCharge::behind($object['charge'] ?? null);

        if ($charge === null) {
            return;
        }

        if ($type === 'charge.refunded' && ! $this->wasRefundedInFull($charge)) {
            return;
        }

        $account = StripeCharge::accountFor($charge);

        if ($account === null) {
            return;
        }

        new EndAccess(
            account: $account,
            reason: $type === 'charge.refunded' ? AccessEndReasonEnum::Refunded : AccessEndReasonEnum::Disputed,
            payment: StripeCharge::paymentFor($charge),
        )->execute();
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
