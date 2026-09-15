<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StructureAppliesToEnum;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\StructureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * Class Structure
 *
 * The stable container for one field structure.
 *
 * It holds no fields and no pages of its own. What it holds is versions, which
 * hold the pages, which hold the section placements. It says which kind of
 * record it describes and which of its versions a new record is given.
 *
 * It exists because the engine used to be named after one of its callers: a
 * version belonged to a template, so nothing but a template could carry fields.
 * An activity type needs the same engine, and the alternatives were to make it
 * borrow a template it is not, or to grow a second field engine beside the
 * first, which the constitution forbids.
 *
 * Its owner is a morph, and a morph carries no foreign key. Deleting an owner
 * is soft and leaves the structure where it is, so that a restored owner comes
 * back with its fields, and the only final deletion is the account's, which the
 * database cascades. A soft deleted owner reads as null through {@see owner()},
 * so the actions that delete an owner refuse while anything still points at one
 * of its versions rather than leaving a caller to meet a structure with no owner.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $owner_type
 * @property int $owner_id
 * @property StructureAppliesToEnum $applies_to
 * @property int|null $entity_type_id
 * @property int|null $published_version_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read EntityType|null $entityType
 * @property-read Template|ActivityType|null $owner
 * @property-read StructureVersion|null $publishedVersion
 */
#[Fillable([
    'account_id',
    'owner_type',
    'owner_id',
    'applies_to',
    'entity_type_id',
    'published_version_id',
])]
class Structure extends Model
{
    /** @use HasFactory<StructureFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'applies_to' => StructureAppliesToEnum::class,
        ];
    }

    /**
     * Get the account the structure belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the thing the structure describes, a template or an activity type.
     *
     * The column carries no foreign key, and the owner is soft deleted, so this
     * is null once the owner is gone. The deleting actions keep that state out
     * of reach of a record, a vault default or a draft.
     *
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the kind of record the structure describes, which only a structure
     * describing an entity has.
     *
     * @return BelongsTo<EntityType, $this>
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * Get every state the structure has been in, newest first.
     *
     * @return HasMany<StructureVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(StructureVersion::class)->orderByDesc('version_number');
    }

    /**
     * Get the version a new record is given. There is none while only drafts
     * exist, which is a structure somebody is still designing.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class, 'published_version_id');
    }
}
