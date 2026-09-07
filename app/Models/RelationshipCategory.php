<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\RelationshipCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class RelationshipCategory
 *
 * A display group for relationship types: Family, Romantic, Friendship,
 * Professional, Other. Every account starts with those five and may add more.
 *
 * A category organises the settings screen and the picker, and does nothing
 * else. Moving a type into Family does not make it genealogical, and moving a
 * supplied genealogical type out of it does not take its behaviour away.
 *
 * A supplied category keeps its `key` for as long as it exists and cannot be
 * renamed or deleted. Its name is stored as a translation key rather than a
 * word, so it reads in the language of whoever is looking.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string|null $key
 * @property string $name
 * @property string|null $name_translation_key
 * @property bool $is_system
 * @property bool $is_active
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 */
#[Fillable([
    'account_id',
    'key',
    'name',
    'name_translation_key',
    'is_system',
    'is_active',
    'position',
])]
class RelationshipCategory extends Model
{
    /** @use HasFactory<RelationshipCategoryFactory> */
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
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Read the name the user gave the category, falling back to the wording the
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
     * Get the account the category belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the types filed under the category, in the order they are shown.
     *
     * @return HasMany<RelationshipType, $this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(RelationshipType::class)->orderBy('position');
    }
}
