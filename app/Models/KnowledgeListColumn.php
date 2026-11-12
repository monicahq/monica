<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\KnowledgeListColumnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class KnowledgeListColumn
 *
 * One column of a Knowledge list, named by its owner: City, Cuisine, Rating.
 * Every cell of the column holds text. Removing the column deletes its cells,
 * and the last column of a list cannot be removed.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $knowledge_list_id
 * @property string $name
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KnowledgeList $list
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'knowledge_list_id',
    'name',
    'position',
])]
class KnowledgeListColumn extends Model
{
    /** @use HasFactory<KnowledgeListColumnFactory> */
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
     * Get the list the column belongs to.
     *
     * @return BelongsTo<KnowledgeList, $this>
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(KnowledgeList::class, 'knowledge_list_id');
    }
}
