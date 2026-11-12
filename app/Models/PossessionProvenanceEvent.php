<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionProvenanceEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class PossessionProvenanceEvent
 *
 * One moment in the history of one possession: where it was made, who
 * acquired, inherited, received or sold it, who held it for a while, when it
 * was lost or recovered, and what authenticated, exhibited or published it.
 * Together the events form the provenance of the possession, oldest first.
 *
 * The date is kept with the precision it is known to. A month is stored as
 * its first day and a year as the first of January; an unknown date is null,
 * and such an event comes last.
 *
 * An event holds no money. A moment that cost something points at the
 * transaction of the same possession that records it, and its figures are read
 * from there. Deleting that transaction keeps the event and clears the link.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property int|null $possession_transaction_id
 * @property PossessionProvenanceEventTypeEnum $type
 * @property string|null $title
 * @property PossessionProvenanceDatePrecisionEnum $date_precision
 * @property CarbonImmutable|null $occurred_on
 * @property string|null $from
 * @property string|null $to
 * @property string|null $location
 * @property string|null $description
 * @property string|null $reference_number
 * @property string|null $source_url
 * @property bool $is_verified
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read PossessionTransaction|null $transaction
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'possession_transaction_id',
    'type',
    'title',
    'date_precision',
    'occurred_on',
    'from',
    'to',
    'location',
    'description',
    'reference_number',
    'source_url',
    'is_verified',
])]
class PossessionProvenanceEvent extends Model
{
    /** @use HasFactory<PossessionProvenanceEventFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => PossessionProvenanceEventTypeEnum::class,
            'date_precision' => PossessionProvenanceDatePrecisionEnum::class,
            'occurred_on' => 'immutable_date',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Get the possession the event belongs to.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the transaction that records what the moment cost, if any.
     *
     * @return BelongsTo<PossessionTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PossessionTransaction::class, 'possession_transaction_id');
    }
}
