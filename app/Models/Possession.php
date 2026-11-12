<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\PossessionStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * Class Possession
 *
 * Something the account owns, recorded in one vault: a car, a bicycle, a mug.
 * It is of one possession type, which never changes once it is recorded, and
 * may sit at one location of the account. It holds one value for each field of
 * its type that somebody filled in, and any of the account's tags.
 *
 * It may record what it would cost to replace today, in one currency. It
 * holds its transactions, money and ownership changing hands, and how, when
 * and from whom it was acquired is read from the one that brought it in, its
 * acquisition transaction, rather than stored on the possession.
 *
 * A possession may be featured, to be shown among the main possessions of its
 * vault. Featured possessions are ordered without a gap; the others carry no
 * position.
 *
 * It holds its location stays, the periods it spent at locations of the
 * account. Its current location is always the location of its open stay, the
 * one with no day it left, and null without one.
 *
 * It holds its loans, the periods it is out of or into the account's hands
 * with one contact of its vault, without changing owner. At most one loan is
 * active at a time.
 *
 * Deleting its type deletes it and its values. A location any of its stays
 * refers to cannot be deleted.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_type_id
 * @property int|null $current_location_id
 * @property string $name
 * @property string|null $description
 * @property string|null $identifier
 * @property PossessionStatusEnum $status
 * @property int $quantity
 * @property bool $is_featured
 * @property int|null $position
 * @property string|null $declared_value
 * @property string|null $declared_value_currency
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read PossessionType $possessionType
 * @property-read Location|null $location
 * @property-read Collection<int, PossessionTransaction> $transactions
 * @property-read Collection<int, PossessionInsuranceRecord> $insuranceRecords
 * @property-read Collection<int, PossessionMaintenanceRecord> $maintenanceRecords
 * @property-read Collection<int, PossessionValuation> $valuations
 * @property-read Collection<int, PossessionProvenanceEvent> $provenanceEvents
 * @property-read Collection<int, PossessionLocationStay> $locationStays
 * @property-read PossessionLocationStay|null $openLocationStay
 * @property-read Collection<int, PossessionLoan> $loans
 * @property-read PossessionLoan|null $activeLoan
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_type_id',
    'current_location_id',
    'name',
    'description',
    'identifier',
    'status',
    'quantity',
    'is_featured',
    'position',
    'declared_value',
    'declared_value_currency',
])]
class Possession extends Model
{
    /** @use HasFactory<PossessionFactory> */
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
            'status' => PossessionStatusEnum::class,
            'quantity' => 'integer',
            'is_featured' => 'boolean',
            'position' => 'integer',
            'declared_value' => 'decimal:2',
        ];
    }

    /**
     * Get the account the possession belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the possession is recorded in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the type of the possession.
     *
     * @return BelongsTo<PossessionType, $this>
     */
    public function possessionType(): BelongsTo
    {
        return $this->belongsTo(PossessionType::class);
    }

    /**
     * Get the location where the possession is now.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Get the values the possession holds for the fields of its type.
     *
     * @return HasMany<PossessionFieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(PossessionFieldValue::class);
    }

    /**
     * Get the transactions recorded for the possession: money and ownership
     * changing hands.
     *
     * @return HasMany<PossessionTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PossessionTransaction::class);
    }

    /**
     * Its transactions, oldest first. An undated one counts as the oldest, since nothing says it came
     * later, and two on the same day are taken in the order they were recorded. Sorted here rather
     * than in SQL because the three databases place a null date differently.
     *
     * @return Collection<int, PossessionTransaction>
     */
    public function transactionsOldestFirst(): Collection
    {
        return $this->transactions
            ->sortBy([
                fn (PossessionTransaction $a, PossessionTransaction $b): int => ($a->occurred_on !== null) <=> ($b->occurred_on !== null),
                fn (PossessionTransaction $a, PossessionTransaction $b): int => $a->occurred_on?->toDateString() <=> $b->occurred_on?->toDateString(),
                fn (PossessionTransaction $a, PossessionTransaction $b): int => $a->id <=> $b->id,
            ])
            ->values();
    }

    /**
     * The transaction that brought the possession in: the earliest purchase, exchange, gift received
     * or inheritance.
     */
    public function acquisitionTransaction(): ?PossessionTransaction
    {
        return $this->transactionsOldestFirst()
            ->first(fn (PossessionTransaction $transaction): bool => $transaction->type->isAcquisition());
    }

    /**
     * Get the insurance records of the possession, one per policy period.
     *
     * @return HasMany<PossessionInsuranceRecord, $this>
     */
    public function insuranceRecords(): HasMany
    {
        return $this->hasMany(PossessionInsuranceRecord::class);
    }

    /**
     * Its insurance records, active first, then pending, expired and cancelled. Within each status
     * the latest start comes first, an undated one after every dated one, and the latest recorded
     * wins a tie. Sorted here rather than in SQL because the three databases place a null date
     * differently.
     *
     * @return Collection<int, PossessionInsuranceRecord>
     */
    public function insuranceRecordsInOrder(): Collection
    {
        return $this->insuranceRecords
            ->sortBy([
                fn (PossessionInsuranceRecord $a, PossessionInsuranceRecord $b): int => $a->status->position() <=> $b->status->position(),
                fn (PossessionInsuranceRecord $a, PossessionInsuranceRecord $b): int => ($b->starts_on !== null) <=> ($a->starts_on !== null),
                fn (PossessionInsuranceRecord $a, PossessionInsuranceRecord $b): int => $b->starts_on?->toDateString() <=> $a->starts_on?->toDateString(),
                fn (PossessionInsuranceRecord $a, PossessionInsuranceRecord $b): int => $b->id <=> $a->id,
            ])
            ->values();
    }

    /**
     * The record the possession is insured under now: the active one with the latest start.
     */
    public function currentInsuranceRecord(): ?PossessionInsuranceRecord
    {
        return $this->insuranceRecordsInOrder()
            ->first(fn (PossessionInsuranceRecord $record): bool => $record->status === PossessionInsuranceStatusEnum::Active);
    }

    /**
     * Get the maintenance records of the possession, one per job, the latest performed first and the
     * latest recorded first on the same day.
     *
     * @return HasMany<PossessionMaintenanceRecord, $this>
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(PossessionMaintenanceRecord::class)
            ->orderByDesc('performed_on')
            ->orderByDesc('id');
    }

    /**
     * Get the valuations of the possession, the latest valued first and the latest recorded first on
     * the same day. The first one is the current value.
     *
     * @return HasMany<PossessionValuation, $this>
     */
    public function valuations(): HasMany
    {
        return $this->hasMany(PossessionValuation::class)
            ->orderByDesc('valued_on')
            ->orderByDesc('id');
    }

    /**
     * Get the provenance events of the possession, oldest first, those with an unknown date last, and
     * the earliest recorded first on the same date.
     *
     * @return HasMany<PossessionProvenanceEvent, $this>
     */
    public function provenanceEvents(): HasMany
    {
        return $this->hasMany(PossessionProvenanceEvent::class)
            ->orderByRaw('occurred_on is null')
            ->orderBy('occurred_on')
            ->orderBy('id');
    }

    /**
     * Get the periods it spent at locations of the account, oldest first. A stay whose arrival is
     * unknown comes first; on the same day, the earliest recorded comes first.
     *
     * @return HasMany<PossessionLocationStay, $this>
     */
    public function locationStays(): HasMany
    {
        return $this->hasMany(PossessionLocationStay::class)
            ->orderByRaw('arrived_on is not null')
            ->orderBy('arrived_on')
            ->orderBy('id');
    }

    /**
     * Get the stay where it is now, the one with no day it left.
     *
     * @return HasOne<PossessionLocationStay, $this>
     */
    public function openLocationStay(): HasOne
    {
        return $this->hasOne(PossessionLocationStay::class)->whereNull('left_on');
    }

    /**
     * Get its loans, oldest first; on the same day, the earliest recorded comes first.
     *
     * @return HasMany<PossessionLoan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(PossessionLoan::class)
            ->orderBy('started_on')
            ->orderBy('id');
    }

    /**
     * Get the loan in progress, the one somebody has it under now.
     *
     * @return HasOne<PossessionLoan, $this>
     */
    public function activeLoan(): HasOne
    {
        return $this->hasOne(PossessionLoan::class)->where('status', PossessionLoanStatusEnum::Active->value);
    }

    /**
     * The valuation that gives the possession its current value: the latest by date, the latest
     * recorded on a tie.
     */
    public function currentValuation(): ?PossessionValuation
    {
        return $this->valuations->first();
    }

    /**
     * What the possession is worth now: its current valuation, else what it would cost to replace,
     * else nothing. Says which of the two it read, because the wording around it differs.
     *
     * @return array{amount: string, currency: string, isValuation: bool}|null
     */
    public function currentValue(): ?array
    {
        $valuation = $this->currentValuation();

        if ($valuation instanceof PossessionValuation) {
            return ['amount' => $valuation->amount, 'currency' => $valuation->currency->value, 'isValuation' => true];
        }

        if ($this->declared_value === null || $this->declared_value_currency === null) {
            return null;
        }

        return ['amount' => $this->declared_value, 'currency' => $this->declared_value_currency, 'isValuation' => false];
    }

    /**
     * The latest maintenance record, when its next due date is before the given day. Only the latest
     * counts, because a later job supersedes the schedule of an earlier one.
     */
    public function overdueMaintenanceRecord(CarbonImmutable $today): ?PossessionMaintenanceRecord
    {
        $latest = $this->maintenanceRecords->first();

        if ($latest?->next_due_on === null) {
            return null;
        }

        return $latest->next_due_on->toDateString() < $today->toDateString() ? $latest : null;
    }

    /**
     * Get the tags the possession carries, by name.
     *
     * @return BelongsToMany<PossessionTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PossessionTag::class)->orderBy('name');
    }
}
