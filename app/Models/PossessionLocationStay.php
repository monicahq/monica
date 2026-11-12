<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionLocationStayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class PossessionLocationStay
 *
 * One period one possession spent at one location of the account. The stay
 * with no day it left is the open one, where the possession is now; a
 * possession has at most one, and its current location is always that stay's
 * location. Together the stays say where it has been since it was recorded.
 *
 * Moving a possession closes the open stay on the day it moved and opens the
 * next on the same day. Correcting a stay changes that stay only, so stays may
 * leave a gap between them, or overlap, exactly as somebody recorded them.
 *
 * The day it arrived may be unknown, as it is for a possession that had a
 * location before stays were kept. A location any stay refers to cannot be
 * deleted, so the cascade on the location is only reached when the account
 * itself goes.
 *
 * It carries the account and the vault of its possession so that an address
 * naming it is scoped like every other record of a vault.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $possession_id
 * @property int $location_id
 * @property CarbonImmutable|null $arrived_on
 * @property CarbonImmutable|null $left_on
 * @property string|null $reason
 * @property string|null $note
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read Location $location
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'possession_id',
    'location_id',
    'arrived_on',
    'left_on',
    'reason',
    'note',
])]
class PossessionLocationStay extends Model
{
    /** @use HasFactory<PossessionLocationStayFactory> */
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
            'arrived_on' => 'immutable_date',
            'left_on' => 'immutable_date',
        ];
    }

    /**
     * Get the possession the stay belongs to.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the location where the possession stayed.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Whether it is the open stay, where the possession is now.
     */
    public function isOpen(): bool
    {
        return $this->left_on === null;
    }
}
