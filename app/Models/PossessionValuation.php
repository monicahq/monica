<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionValuationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

/**
 * Class PossessionValuation
 *
 * One estimate of what one possession is worth on one date: an own
 * estimate, an appraisal, an insurance declaration, an auction result, a
 * comparable listing or an offer received. A valuation is never edited once
 * recorded. To correct a figure, a new valuation is recorded, so the earlier
 * ones stay as the history of how the value moved.
 *
 * The latest by date is the current value of the possession. The amount is in
 * the one currency of the valuation, and nothing is converted. The confidence
 * says how sure somebody is of the figure, and is unknown unless chosen.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property PossessionValuationTypeEnum $type
 * @property CarbonImmutable $valued_on
 * @property PossessionValuationConfidenceEnum $confidence
 * @property string $amount
 * @property CurrencyEnum $currency
 * @property string|null $valuer
 * @property string|null $method
 * @property string|null $reference_number
 * @property string|null $source_url
 * @property string|null $note
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read Collection<int, File> $documents
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'type',
    'valued_on',
    'confidence',
    'amount',
    'currency',
    'valuer',
    'method',
    'reference_number',
    'source_url',
    'note',
])]
class PossessionValuation extends Model
{
    /** @use HasFactory<PossessionValuationFactory> */
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
            'type' => PossessionValuationTypeEnum::class,
            'valued_on' => 'immutable_date',
            'confidence' => PossessionValuationConfidenceEnum::class,
            'amount' => 'decimal:2',
            'currency' => CurrencyEnum::class,
        ];
    }

    /**
     * Get the possession the valuation estimates.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the documents attached to the valuation: a certificate, a listing,
     * an offer letter.
     *
     * @return BelongsToMany<File, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'possession_valuation_file')->withTimestamps();
    }
}
