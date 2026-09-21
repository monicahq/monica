<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\LifeEventCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class LifeEventCategory
 *
 * A group for life event natures: Relationships and family, Work and education,
 * Health and wellbeing, Home and daily life, Leisure and culture, Travel and
 * experiences, Personal, Other. Every account starts with those eight and may
 * add more.
 *
 * A category organises the vocabulary in settings and pickers, filters the
 * journal and gives aggregate statistics a level to count at. It never gives an
 * life event any behaviour: moving Call into Personal does not stop it counting
 * as an interaction, and moving Thought into Relationships and family does not
 * make it count as one.
 *
 * A supplied category keeps its `key` for as long as it exists and cannot be
 * deleted. It may be renamed like any other, which is what the product
 * specification says in 9.3: being supplied governs deletion and nothing else.
 * Its name is stored as a translation key rather than a word until somebody
 * rewrites it, so it reads in the language of whoever is looking.
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
 * @property-read Collection<int, LifeEventType> $lifeEventTypes
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
class LifeEventCategory extends Model
{
    /** @use HasFactory<LifeEventCategoryFactory> */
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
     * Get the life event natures filed under this category.
     *
     * @return HasMany<LifeEventType, $this>
     */
    public function lifeEventTypes(): HasMany
    {
        return $this->hasMany(LifeEventType::class);
    }

    /**
     * How many life event natures are filed under this category.
     *
     * Two things read it and they have to agree: the number the settings rail
     * shows beside a category, and the rule that a category still holding
     * natures cannot be deleted. A soft deleted nature is not one of them.
     */
    public function lifeEventTypeCount(): int
    {
        return $this->lifeEventTypes()->count();
    }
}
