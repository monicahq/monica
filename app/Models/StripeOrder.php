<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use Carbon\CarbonImmutable;
use Database\Factories\StripeOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StripeOrder
 *
 * One thing the account ordered outright at Stripe, written when the checkout
 * session opens and settled once Stripe says the session is paid. A settled
 * order is one of the three things that hold full access, beside a valid
 * subscription and an administrator's grant.
 *
 * The row is keyed on the session, so a buyer who reloads the page Stripe sends
 * them back to settles one order rather than adding a second. It is never
 * deleted: money given back leaves it in place carrying what Stripe now says of
 * it, which is what lets a screen say when access ended and why.
 *
 * A subscription is never written here, and neither is a grant, since neither
 * is ordered.
 *
 * @property int $id
 * @property int $account_id
 * @property string $type
 * @property string $stripe_id
 * @property string $stripe_status
 * @property string $stripe_price
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'account_id',
    'type',
    'stripe_id',
    'stripe_status',
    'stripe_price',
])]
class StripeOrder extends Model
{
    /** @use HasFactory<StripeOrderFactory> */
    use HasFactory;

    /**
     * The states this application acts on, in the words Stripe writes them.
     * Stripe's vocabulary is wider than these four, and any other word it sends
     * is simply not paid.
     */
    public const string UNPAID = 'unpaid';

    public const string PAID = 'paid';

    public const string REFUNDED = 'refunded';

    public const string DISPUTED = 'disputed';

    /**
     * The name every order is addressed by, the way Cashier addresses a
     * subscription by one. An account orders one thing, so there is only ever
     * this name.
     */
    public const string DEFAULT_TYPE = 'default';

    /**
     * Get the account that ordered.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get whether the order holds anything. Only a paid one does: an abandoned
     * session stays unpaid for good, and money given back takes it out again.
     */
    public function isSettled(): bool
    {
        return $this->stripe_status === self::PAID;
    }

    /**
     * Get the offer this order was placed against, read through the catalogue
     * rather than stored, null once Stripe no longer knows the price. A settled
     * order holds full access whatever the answer, so this only names it.
     */
    public function offerKind(): ?OfferKindEnum
    {
        return StripeCatalogue::kindOf($this->stripe_price);
    }
}
