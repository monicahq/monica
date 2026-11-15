<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\StripeEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StripeEvent
 *
 * One event Stripe sent to the webhook and the application acted on. Stripe
 * may deliver the same event more than once, and the row is what makes the
 * second delivery change nothing.
 *
 * Stripe stops retrying an event after three days, so a row is kept for thirty
 * and then pruned by the daily `model:prune`.
 *
 * @property int $id
 * @property string $stripe_id
 * @property string $type
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'stripe_id',
    'type',
])]
class StripeEvent extends Model
{
    /** @use HasFactory<StripeEventFactory> */
    use HasFactory;

    use MassPrunable;

    public const int RETENTION_IN_DAYS = 30;

    /**
     * The events old enough that Stripe will never send them again.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subDays(self::RETENTION_IN_DAYS));
    }
}
