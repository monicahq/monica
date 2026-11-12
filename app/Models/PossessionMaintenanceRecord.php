<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionMaintenanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class PossessionMaintenanceRecord
 *
 * One job done on one possession: an inspection, a cleaning, a repair, a
 * restoration. It says what was done, by whom, when, for how much, what state
 * the possession was in before and after, and when it should be looked at
 * again. Each job is a record of its own, so the records together are the
 * history of the work on that copy.
 *
 * The conditions are the account's possession conditions. Deleting one
 * empties that side of the records naming it rather than deleting them. The
 * cost is in the currency of the record, never converted, and never counted
 * in the transactions or the declared value.
 *
 * Whether a record is overdue is not stored: only the latest record of the
 * possession can be, and the possession answers it.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property PossessionMaintenanceTypeEnum $type
 * @property string $title
 * @property string|null $performed_by
 * @property CarbonImmutable $performed_on
 * @property string|null $cost
 * @property CurrencyEnum|null $currency
 * @property int|null $condition_before_id
 * @property int|null $condition_after_id
 * @property CarbonImmutable|null $next_due_on
 * @property string|null $description
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read PossessionCondition|null $conditionBefore
 * @property-read PossessionCondition|null $conditionAfter
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'type',
    'title',
    'performed_by',
    'performed_on',
    'cost',
    'currency',
    'condition_before_id',
    'condition_after_id',
    'next_due_on',
    'description',
])]
class PossessionMaintenanceRecord extends Model
{
    /** @use HasFactory<PossessionMaintenanceRecordFactory> */
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
            'type' => PossessionMaintenanceTypeEnum::class,
            'performed_on' => 'immutable_date',
            'cost' => 'decimal:2',
            'currency' => CurrencyEnum::class,
            'next_due_on' => 'immutable_date',
        ];
    }

    /**
     * Get the possession the job was done on.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the condition the possession was in before the job.
     *
     * @return BelongsTo<PossessionCondition, $this>
     */
    public function conditionBefore(): BelongsTo
    {
        return $this->belongsTo(PossessionCondition::class, 'condition_before_id');
    }

    /**
     * Get the condition the possession was in after the job.
     *
     * @return BelongsTo<PossessionCondition, $this>
     */
    public function conditionAfter(): BelongsTo
    {
        return $this->belongsTo(PossessionCondition::class, 'condition_after_id');
    }
}
