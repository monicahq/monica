<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * One decision moving many records of one kind, in one vault, from one template onto one version.
 *
 * Somebody looks at the statistics of a kind of record, picks a template and the versions of it to
 * leave, picks where to go, reads the preview and confirms. That confirmation is this row. The
 * records it takes are its items, written once when it is confirmed, so the batch never grows or
 * shrinks afterwards and a record moved by somebody else in the meantime is skipped rather than
 * moved onto something nobody previewed.
 *
 * The move itself happens on the queue, in slices, and the counts are written once the last item
 * has an outcome, along with `ended_at`, which is what says the change is over. A finished change is
 * never written again. The uuid is the source detail of the
 * one log entry the change writes for the whole batch.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int|null $user_id
 * @property int|null $entity_type_id
 * @property int $from_template_id
 * @property list<int> $from_version_ids
 * @property int $structure_version_id
 * @property int $items_total
 * @property int $moved_count
 * @property int $skipped_count
 * @property int $failed_count
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read User|null $user
 * @property-read EntityType|null $entityType
 * @property-read Template $fromTemplate
 * @property-read StructureVersion $structureVersion
 * @property-read Collection<int, TemplateChangeItem> $items
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'user_id',
    'entity_type_id',
    'from_template_id',
    'from_version_ids',
    'structure_version_id',
    'items_total',
    'moved_count',
    'skipped_count',
    'failed_count',
    'ended_at',
])]
class TemplateChange extends Model
{
    /** @use HasFactory<TemplateChangeFactory> */
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
            'from_version_ids' => 'array',
            'items_total' => 'integer',
            'moved_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the account of the change.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault whose records it moves.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the user who confirmed it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the kind of record it moves, none for contacts.
     *
     * @return BelongsTo<EntityType, $this>
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * Get the template the records leave.
     *
     * @return BelongsTo<Template, $this>
     */
    public function fromTemplate(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'from_template_id');
    }

    /**
     * Get the version the records move onto.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function structureVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class);
    }

    /**
     * Get the records of the batch and what became of each.
     *
     * @return HasMany<TemplateChangeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TemplateChangeItem::class);
    }

    /**
     * Whether some of its records are still waiting to be dealt with.
     */
    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }
}
