<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionInsuranceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class PossessionInsuranceRecord
 *
 * One policy period covering one possession: who insures it, for how much,
 * and until when. When a policy renews with a new insured value, or the
 * insurer changes, a new record is added rather than the old one edited, so
 * the earlier records stay as the history of how the possession was covered.
 *
 * The status is the one entered and never follows the dates. The insured
 * value and the excess are in the one currency of the record, and nothing is
 * converted. Either date may be unknown, and a policy without an end date
 * renews on its own.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property string $insurer
 * @property PossessionInsuranceStatusEnum $status
 * @property string|null $policy_number
 * @property string|null $coverage
 * @property string $insured_value
 * @property string|null $excess
 * @property CurrencyEnum $currency
 * @property CarbonImmutable|null $starts_on
 * @property CarbonImmutable|null $ends_on
 * @property bool $is_listed_individually
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $note
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'insurer',
    'status',
    'policy_number',
    'coverage',
    'insured_value',
    'excess',
    'currency',
    'starts_on',
    'ends_on',
    'is_listed_individually',
    'contact_name',
    'contact_email',
    'contact_phone',
    'note',
])]
class PossessionInsuranceRecord extends Model
{
    /** @use HasFactory<PossessionInsuranceRecordFactory> */
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
            'status' => PossessionInsuranceStatusEnum::class,
            'insured_value' => 'decimal:2',
            'excess' => 'decimal:2',
            'currency' => CurrencyEnum::class,
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'is_listed_individually' => 'boolean',
        ];
    }

    /**
     * Get the possession the record covers.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }
}
