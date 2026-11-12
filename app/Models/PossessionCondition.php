<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionConditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class PossessionCondition
 *
 * The state a possession is in: new, used, broken. Conditions belong to the
 * account rather than to a vault, as possession tags do, so every vault uses
 * the same list. Every account starts with six, written in the language of its
 * person, which are then ordinary conditions it may change or delete.
 *
 * A condition carries a name, unique in the account ignoring case, one of the
 * vault colours, an optional glyph from the section catalogue, and a position:
 * the account keeps its conditions in an order running from best to worst,
 * numbered from 1 without gaps. Possessions cannot carry a condition yet.
 * Maintenance records name one before and one after a job; deleting a
 * condition empties that side of those records and deletes nothing else.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property string $colour
 * @property string|null $icon
 * @property int $position
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
    'position',
])]
class PossessionCondition extends Model
{
    /** @use HasFactory<PossessionConditionFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the condition belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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
