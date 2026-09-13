<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RelationshipBehaviorEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\RelationshipTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class RelationshipType
 *
 * The nature of a link between two people: Parent / Child, Sibling, Partner,
 * Friend, Colleague. It is what somebody picks when they write down that two
 * people are connected.
 *
 * A type says how the link reads from each side. A symmetric one uses the same
 * word for both people, as Friend does. A directed one uses a different word on
 * each side, as Parent / Child does, and A always carries `role_a` while B
 * always carries `role_b`. That is why the direction is frozen once a type is
 * supplied or in use: turning it round would rewrite relationships written
 * years ago without anybody asking.
 *
 * Four supplied types carry a protected behaviour. Parent / Child feeds the
 * family tree, and Partner, Fiancé and Spouse feed romantic history. The
 * behaviour is attached to the stable type and never to its name or its
 * category, so renaming one or moving it elsewhere changes the words on screen
 * and nothing more. A type somebody invents never acquires one.
 *
 * The supplied names and roles are stored as translation keys rather than
 * words, so they read in the language of whoever is looking, until the day
 * somebody rewrites them and their own words take over.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $relationship_category_id
 * @property string|null $key
 * @property string $name
 * @property string|null $name_translation_key
 * @property string $role_a
 * @property string|null $role_a_translation_key
 * @property string $role_b
 * @property string|null $role_b_translation_key
 * @property bool $is_symmetric
 * @property RelationshipBehaviorEnum|null $system_behavior
 * @property bool $is_system
 * @property bool $is_active
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read RelationshipCategory $relationshipCategory
 * @property-read Collection<int, Relationship> $relationships
 */
#[Fillable([
    'account_id',
    'relationship_category_id',
    'key',
    'name',
    'name_translation_key',
    'role_a',
    'role_a_translation_key',
    'role_b',
    'role_b_translation_key',
    'is_symmetric',
    'system_behavior',
    'is_system',
    'is_active',
    'position',
])]
class RelationshipType extends Model
{
    /** @use HasFactory<RelationshipTypeFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_symmetric' => 'boolean',
            'system_behavior' => RelationshipBehaviorEnum::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * How many relationships are written with this type.
     *
     * It is asked for here rather than at each call site because two rules turn
     * on it: a type in use keeps its direction, and a type in use cannot be
     * deleted. A soft deleted relationship is not in use.
     */
    public function relationshipCount(): int
    {
        return $this->relationships()->count();
    }

    /**
     * Get the relationships written with this type.
     *
     * @return HasMany<Relationship, $this>
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class);
    }

    /**
     * Read the name the user gave the type, falling back to the wording the
     * product supplied it with, translated.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->name_translation_key),
        );
    }

    /**
     * Read how A relates to B, the user's word first.
     *
     * @return Attribute<string, string>
     */
    protected function roleA(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->role_a_translation_key),
        );
    }

    /**
     * Read how B relates to A, the user's word first.
     *
     * @return Attribute<string, string>
     */
    protected function roleB(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->role_b_translation_key),
        );
    }

    /**
     * Limit a query to the types that may be picked for a new relationship.
     *
     * @param  Builder<RelationshipType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the account the type belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the category the type is filed under.
     *
     * @return BelongsTo<RelationshipCategory, $this>
     */
    public function relationshipCategory(): BelongsTo
    {
        return $this->belongsTo(RelationshipCategory::class);
    }
}
