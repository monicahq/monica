<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Location
 *
 * A place: a town, a house, a room, a shelf, a box. Locations belong to the
 * account rather than to a vault, since the same house holds the things of
 * every vault. A location may sit inside another one, as deep as somebody
 * wants, so a place is described by the path of the locations around it. No
 * kind and no level is imposed.
 *
 * A location is not a thing owned. A house recorded as an owned item and the
 * house as a place stay two records, and neither creates the other.
 *
 * Its parent is always in the same account, and never the location itself or
 * a location inside it. A location holding another, or one any possession
 * stayed at, cannot be deleted, so the cascades on the parent and on the stays
 * are only reached when the account itself goes.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $emoji
 * @property string|null $address
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Location|null $parent
 * @property-read Collection<int, PossessionLocationStay> $stays
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'parent_id',
    'name',
    'emoji',
    'address',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the location belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the location it sits inside, null at the top level.
     *
     * @return BelongsTo<Location, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /**
     * Get the locations sitting directly inside it.
     *
     * @return HasMany<Location, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    /**
     * Get the periods possessions spent here, open or past.
     *
     * @return HasMany<PossessionLocationStay, $this>
     */
    public function stays(): HasMany
    {
        return $this->hasMany(PossessionLocationStay::class);
    }
}
