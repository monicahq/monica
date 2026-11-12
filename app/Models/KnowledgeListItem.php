<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\KnowledgeListItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class KnowledgeListItem
 *
 * One row of a Knowledge list. Its texts are cells, one per column at most,
 * kept in their own table so that removing a column takes its cells with it.
 * A blank cell is no row at all, and an item whose cells would all be blank
 * is not saved.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $knowledge_list_id
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KnowledgeList $list
 * @property-read Collection<int, KnowledgeListCell> $cells
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'knowledge_list_id',
    'position',
])]
class KnowledgeListItem extends Model
{
    /** @use HasFactory<KnowledgeListItemFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /**
     * The parent whose last edit this one is.
     *
     * @var list<string>
     */
    protected $touches = ['list'];

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

    /**
     * Get the list the item belongs to.
     *
     * @return BelongsTo<KnowledgeList, $this>
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(KnowledgeList::class, 'knowledge_list_id');
    }

    /**
     * Get the cells of the item.
     *
     * @return HasMany<KnowledgeListCell, $this>
     */
    public function cells(): HasMany
    {
        return $this->hasMany(KnowledgeListCell::class);
    }

    /**
     * The text the item holds in a column, or null when that cell is blank.
     */
    public function valueFor(KnowledgeListColumn $column): ?string
    {
        return $this->cells->firstWhere('knowledge_list_column_id', $column->id)?->value;
    }
}
