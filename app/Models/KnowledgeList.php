<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\KnowledgeListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class KnowledgeList
 *
 * A table somebody keeps, inside one folder of a vault's Knowledge: the books
 * they have read, the paint reference of each room. Its owner names its
 * columns, and each item holds one text per column.
 *
 * The columns are the second place user defined fields live outside
 * templates, after the possession type's fields. They hold text only, and
 * each is a row of its own, never a database column.
 *
 * A list keeps at least one column. Its items read in their position.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $knowledge_folder_id
 * @property string $title
 * @property string|null $description
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KnowledgeFolder $folder
 * @property-read Collection<int, KnowledgeListColumn> $columns
 * @property-read Collection<int, KnowledgeListItem> $items
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'knowledge_folder_id',
    'title',
    'description',
])]
class KnowledgeList extends Model
{
    public const int MAX_DESCRIPTION_LENGTH = 5000;

    /** @use HasFactory<KnowledgeListFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /**
     * Get the folder the list sits in.
     *
     * @return BelongsTo<KnowledgeFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(KnowledgeFolder::class, 'knowledge_folder_id');
    }

    /**
     * Get the columns of the list, in their order.
     *
     * @return HasMany<KnowledgeListColumn, $this>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(KnowledgeListColumn::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Get the items of the list, in their order.
     *
     * @return HasMany<KnowledgeListItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KnowledgeListItem::class)->orderBy('position')->orderBy('id');
    }
}
