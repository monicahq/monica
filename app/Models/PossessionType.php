<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class PossessionType
 *
 * A kind of thing the account owns: office equipment, a vehicle, a musical
 * instrument. It says which details a thing of that kind carries, through its
 * groups and its fields. Types belong to the account rather than to a vault,
 * as locations do.
 *
 * It is the one place user defined fields live outside the template engine.
 * The fields are its own rows, which is why the constitution lists it among
 * the fixed concepts.
 *
 * Deleting a type deletes its groups, its fields, and every possession of the
 * type with the values it held.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property string $colour
 * @property string|null $icon
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'name',
    'colour',
    'icon',
])]
class PossessionType extends Model
{
    /** @use HasFactory<PossessionTypeFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the type belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the groups of fields of the type, in order.
     *
     * @return HasMany<PossessionFieldGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(PossessionFieldGroup::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Get every field of the type, grouped or not.
     *
     * @return HasMany<PossessionField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(PossessionField::class);
    }

    /**
     * Get the possessions of the type, in every vault of the account.
     *
     * @return HasMany<Possession, $this>
     */
    public function possessions(): HasMany
    {
        return $this->hasMany(Possession::class);
    }

    /**
     * Get the fields standing outside any group, in order.
     *
     * @return HasMany<PossessionField, $this>
     */
    public function standaloneFields(): HasMany
    {
        return $this->hasMany(PossessionField::class)
            ->whereNull('possession_field_group_id')
            ->orderBy('position')
            ->orderBy('id');
    }
}
