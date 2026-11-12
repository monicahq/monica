<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionLoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class PossessionLoan
 *
 * One period one possession is out of the account's hands with one contact of
 * its vault, or in its hands from them, without changing owner. A loan is
 * planned, active or returned, and a possession has at most one active loan:
 * the one saying who has it now. Overdue is never stored; it is an active loan
 * whose due day has passed.
 *
 * A loan records no transaction. It may name the condition the possession was
 * in when it left and when it came back, from the account's conditions, and a
 * deposit in one currency.
 *
 * Deleting the contact keeps the loan: a contact is soft deleted first, and
 * the loan then names a deleted contact; deleted for good, the contact column
 * is emptied. Merging contacts moves the loan to the contact kept.
 *
 * A loan in the provenance keeps one change of custody event for its start,
 * while it is active or returned, and one for its return, while it is
 * returned. Taking it out of the provenance, or deleting it, deletes both.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property int|null $contact_id
 * @property PossessionLoanDirectionEnum $direction
 * @property PossessionLoanStatusEnum $status
 * @property CarbonImmutable $started_on
 * @property CarbonImmutable|null $due_on
 * @property CarbonImmutable|null $returned_on
 * @property int|null $condition_out_id
 * @property int|null $condition_back_id
 * @property string|null $deposit_amount
 * @property CurrencyEnum|null $deposit_currency
 * @property string|null $purpose
 * @property bool $is_in_provenance
 * @property int|null $departure_provenance_event_id
 * @property int|null $return_provenance_event_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read Contact|null $contact
 * @property-read PossessionCondition|null $conditionOut
 * @property-read PossessionCondition|null $conditionBack
 * @property-read PossessionProvenanceEvent|null $departureEvent
 * @property-read PossessionProvenanceEvent|null $returnEvent
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'contact_id',
    'direction',
    'status',
    'started_on',
    'due_on',
    'returned_on',
    'condition_out_id',
    'condition_back_id',
    'deposit_amount',
    'deposit_currency',
    'purpose',
    'is_in_provenance',
    'departure_provenance_event_id',
    'return_provenance_event_id',
])]
class PossessionLoan extends Model
{
    /** @use HasFactory<PossessionLoanFactory> */
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
            'direction' => PossessionLoanDirectionEnum::class,
            'status' => PossessionLoanStatusEnum::class,
            'started_on' => 'immutable_date',
            'due_on' => 'immutable_date',
            'returned_on' => 'immutable_date',
            'deposit_amount' => 'decimal:2',
            'deposit_currency' => CurrencyEnum::class,
            'is_in_provenance' => 'boolean',
        ];
    }

    /**
     * Get the possession the loan belongs to.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the contact who has it or lent it, deleted or not.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    /**
     * Get the condition it was in when it left.
     *
     * @return BelongsTo<PossessionCondition, $this>
     */
    public function conditionOut(): BelongsTo
    {
        return $this->belongsTo(PossessionCondition::class, 'condition_out_id');
    }

    /**
     * Get the condition it was in when it came back.
     *
     * @return BelongsTo<PossessionCondition, $this>
     */
    public function conditionBack(): BelongsTo
    {
        return $this->belongsTo(PossessionCondition::class, 'condition_back_id');
    }

    /**
     * Get the provenance event recording the start of the loan.
     *
     * @return BelongsTo<PossessionProvenanceEvent, $this>
     */
    public function departureEvent(): BelongsTo
    {
        return $this->belongsTo(PossessionProvenanceEvent::class, 'departure_provenance_event_id');
    }

    /**
     * Get the provenance event recording the return.
     *
     * @return BelongsTo<PossessionProvenanceEvent, $this>
     */
    public function returnEvent(): BelongsTo
    {
        return $this->belongsTo(PossessionProvenanceEvent::class, 'return_provenance_event_id');
    }

    /**
     * Whether it is active and its due day is before the given day.
     */
    public function isOverdueOn(CarbonImmutable $today): bool
    {
        if ($this->status !== PossessionLoanStatusEnum::Active) {
            return false;
        }

        if ($this->due_on === null) {
            return false;
        }

        return $this->due_on->lt($today->startOfDay());
    }

    /**
     * The name of the contact, or null once the contact is deleted.
     */
    public function contactName(): ?string
    {
        if ($this->contact === null || $this->contact->trashed()) {
            return null;
        }

        return $this->contact->name;
    }
}
