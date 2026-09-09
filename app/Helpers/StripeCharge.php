<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

/**
 * Reads a charge Stripe is telling us about, and finds what it belongs to here.
 *
 * Refunds and disputes name the charge rather than carrying it, and a charge names its customer
 * rather than the account, so every listener that acts on money going back or coming back has the
 * same three questions to answer. They are answered in one place.
 */
class StripeCharge
{
    /**
     * The charge itself, whether Stripe sent it whole or only named it. Naming it costs one round
     * trip, which is why the listeners that need this are queued.
     *
     * @return array<string, mixed>|null
     */
    public static function behind(mixed $charge): ?array
    {
        if (is_array($charge)) {
            return $charge;
        }

        if (! is_string($charge) || $charge === '') {
            return null;
        }

        try {
            return Cashier::stripe()->charges->retrieve($charge)->toArray();
        } catch (Exception $exception) {
            Log::warning('Stripe named a charge it would not then answer for.', [
                'charge' => $charge,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The account the charge was made against. One naming a customer we do not know, which is what a
     * deleted account looks like from Stripe, belongs to nobody here.
     *
     * @param  array<string, mixed>  $charge
     */
    public static function accountFor(array $charge): ?Account
    {
        $customerId = $charge['customer'] ?? null;

        if (is_array($customerId)) {
            $customerId = $customerId['id'] ?? null;
        }

        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        return Account::query()->where('stripe_id', $customerId)->first();
    }

    /**
     * The payment the charge settled, when the charge names the invoice behind it. Null leaves the
     * caller to work from what the account holds instead, which ends in the same place.
     *
     * @param  array<string, mixed>  $charge
     */
    public static function paymentFor(array $charge): ?Payment
    {
        $invoiceId = $charge['invoice'] ?? null;

        if (is_array($invoiceId)) {
            $invoiceId = $invoiceId['id'] ?? null;
        }

        if (! is_string($invoiceId) || $invoiceId === '') {
            return null;
        }

        return Payment::query()->where('stripe_id', $invoiceId)->first();
    }
}
