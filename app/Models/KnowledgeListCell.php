<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\KnowledgeListCellFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class KnowledgeListCell
 *
 * The text one item of a Knowledge list holds in one of its columns. It has no
 * address of its own: it is always reached through its item.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property int $knowledge_list_item_id
 * @property int $knowledge_list_column_id
 * @property string $value
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KnowledgeListItem $item
 * @property-read KnowledgeListColumn $column
 */
#[Fillable([
    'account_id',
    'vault_id',
    'knowledge_list_item_id',
    'knowledge_list_column_id',
    'value',
])]
class KnowledgeListCell extends Model
{
    /** @use HasFactory<KnowledgeListCellFactory> */
    use HasFactory;

    /**
     * The parent whose last edit this one is.
     *
     * @var list<string>
     */
    protected $touches = ['item'];

    /**
     * Get the item the cell belongs to.
     *
     * @return BelongsTo<KnowledgeListItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(KnowledgeListItem::class, 'knowledge_list_item_id');
    }

    /**
     * Get the column the cell fills.
     *
     * @return BelongsTo<KnowledgeListColumn, $this>
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(KnowledgeListColumn::class, 'knowledge_list_column_id');
    }
}
