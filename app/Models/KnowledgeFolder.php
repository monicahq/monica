<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\KnowledgeFolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class KnowledgeFolder
 *
 * A folder of a vault's Knowledge, grouping notes and lists by theme: Home,
 * Travel, Health. Folders are flat, never nested, and read in the order the
 * person gives them.
 *
 * A vault that holds Knowledge keeps at least one folder, because every note
 * and every list sits in one. Deleting a folder moves its notes and lists to
 * another folder of the vault, or deletes them with it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property string $name
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vault $vault
 * @property-read Collection<int, KnowledgeNote> $notes
 * @property-read Collection<int, KnowledgeList> $lists
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'name',
    'position',
])]
class KnowledgeFolder extends Model
{
    /** @use HasFactory<KnowledgeFolderFactory> */
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
            'position' => 'integer',
        ];
    }

    /**
     * Get the vault the folder belongs to.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the notes in the folder.
     *
     * @return HasMany<KnowledgeNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(KnowledgeNote::class);
    }

    /**
     * Get the lists in the folder.
     *
     * @return HasMany<KnowledgeList, $this>
     */
    public function lists(): HasMany
    {
        return $this->hasMany(KnowledgeList::class);
    }
}
