<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OfferKindEnum;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;
use Laravel\Cashier\Cashier;
use Override;

/**
 * Class Payment
 *
 * One invoice the account paid at Stripe, whether for the one time purchase or
 * for a renewal. Stripe keeps the invoices and answers them from its API, which
 * no screen can afford to wait on, so what a screen shows is copied here when
 * the payment is confirmed and read from here afterwards.
 *
 * The row is keyed on the invoice, so a confirmation Stripe sends twice updates
 * one row rather than adding a second.
 *
 * @property int $id
 * @property int $account_id
 * @property string $stripe_id
 * @property string|null $stripe_subscription_id
 * @property OfferKindEnum|null $kind
 * @property string $status
 * @property int $total
 * @property int $tax
 * @property string $currency
 * @property string|null $invoice_number
 * @property string|null $hosted_invoice_url
 * @property string|null $invoice_pdf_url
 * @property CarbonImmutable $billed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'account_id',
    'stripe_id',
    'stripe_subscription_id',
    'kind',
    'status',
    'total',
    'tax',
    'currency',
    'invoice_number',
    'hosted_invoice_url',
    'invoice_pdf_url',
    'billed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'kind' => OfferKindEnum::class,
            'total' => 'integer',
            'tax' => 'integer',
            'billed_at' => 'datetime',
        ];
    }

    /**
     * Get the account that paid.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * What was paid, written the way the reader reads money rather than in the
     * instance's default locale.
     */
    public function total(): string
    {
        return Cashier::formatAmount($this->total, $this->currency, App::currentLocale());
    }

    /**
     * The tax share of that total, written the same way.
     */
    public function tax(): string
    {
        return Cashier::formatAmount($this->tax, $this->currency, App::currentLocale());
    }

    /**
     * Whether the invoice was raised for a subscription rather than for the one
     * time purchase. It is read off the invoice itself and never off the price's
     * tag, so a mislabelled price cannot grant access for good.
     */
    public function isForSubscription(): bool
    {
        return $this->stripe_subscription_id !== null;
    }
}
