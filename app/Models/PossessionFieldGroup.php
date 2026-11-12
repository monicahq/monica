<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionFieldGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class PossessionFieldGroup
 *
 * A named set of fields within a possession type, such as "Purchase" or
 * "Technical details", which keeps a type with many fields readable. Groups
 * are ordered among themselves, and each orders its own fields.
 *
 * Removing a group keeps its fields: they become standalone fields of the
 * type, after the ones already there.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $possession_type_id
 * @property string $name
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PossessionType $possessionType
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'possession_type_id',
    'name',
    'position',
])]
class PossessionFieldGroup extends Model
{
    /** @use HasFactory<PossessionFieldGroupFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the group belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the type the group belongs to.
     *
     * @return BelongsTo<PossessionType, $this>
     */
    public function possessionType(): BelongsTo
    {
        return $this->belongsTo(PossessionType::class);
    }

    /**
     * Get the fields sitting in the group, in order.
     *
     * @return HasMany<PossessionField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(PossessionField::class)->orderBy('position')->orderBy('id');
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
            'position' => 'integer',
        ];
    }
}
