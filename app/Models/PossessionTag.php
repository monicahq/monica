<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\PossessionTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class PossessionTag
 *
 * A word the account puts on its possessions to sort them across types and
 * locations: insured, to sell, fragile. Tags belong to the account rather than
 * to a vault, as possession types do, so every vault uses the same list.
 *
 * A tag carries a name, unique in the account ignoring case, and one of the
 * vault colours. A possession carries any number of tags, each once, and
 * deleting a tag takes it off the possessions carrying it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property string $colour
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read int|null $possessions_count
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'name',
    'colour',
])]
class PossessionTag extends Model
{
    /** @use HasFactory<PossessionTagFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the tag belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the possessions carrying the tag.
     *
     * @return BelongsToMany<Possession, $this>
     */
    public function possessions(): BelongsToMany
    {
        return $this->belongsToMany(Possession::class);
    }
}
