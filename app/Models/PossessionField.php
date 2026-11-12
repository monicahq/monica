<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PossessionFieldKindEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class PossessionField
 *
 * One detail a possession type asks for: a serial number, a power rating,
 * the end of a warranty. Its kind says what it holds, and a select field
 * carries its choices in order.
 *
 * A field sits in one group of its type, or stands alone. The group is chosen
 * when the field is created and never changes afterwards; removing the group
 * makes the field a standalone one. The position orders the field within its
 * group, or among the standalone fields.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $possession_type_id
 * @property int|null $possession_field_group_id
 * @property string $name
 * @property PossessionFieldKindEnum $kind
 * @property list<string>|null $options
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PossessionType $possessionType
 * @property-read PossessionFieldGroup|null $group
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'possession_type_id',
    'possession_field_group_id',
    'name',
    'kind',
    'options',
    'position',
])]
class PossessionField extends Model
{
    /** @use HasFactory<PossessionFieldFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the field belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the type the field belongs to.
     *
     * @return BelongsTo<PossessionType, $this>
     */
    public function possessionType(): BelongsTo
    {
        return $this->belongsTo(PossessionType::class);
    }

    /**
     * Get the group the field sits in, null when it stands alone.
     *
     * @return BelongsTo<PossessionFieldGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PossessionFieldGroup::class, 'possession_field_group_id');
    }

    /**
     * Get the values possessions hold for the field.
     *
     * @return HasMany<PossessionFieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(PossessionFieldValue::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'kind' => PossessionFieldKindEnum::class,
            'options' => 'array',
            'position' => 'integer',
        ];
    }
}
