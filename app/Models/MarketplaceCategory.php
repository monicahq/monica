<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MarketplaceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class MarketplaceCategory
 *
 * One of the categories an author picks from when publishing: Health, Finance,
 * Animals, and so on.
 *
 * The list is maintained by us and there are no free tags. A catalogue with
 * open keywords becomes unreadable, and the whole value of the marketplace is
 * that somebody can find the medical follow up block without wading through
 * forty near duplicates.
 *
 * This is the one model with no account. It is instance level data, shared by
 * every account and seeded from {@see MarketplaceCategoryEnum}.
 * Names and descriptions are translation keys rather than stored strings,
 * because the catalogue is browsed by people in every language the product
 * supports and the categories are ours to translate, unlike the content inside
 * them.
 *
 * @property int $id
 * @property string $key
 * @property string $name_translation_key
 * @property string $description_translation_key
 * @property string $icon
 * @property int|null $parent_id
 * @property int $position
 * @property bool $is_active
 * @property int $listing_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read string $name
 * @property-read string $description
 * @property-read MarketplaceCategory|null $parent
 */
/*
 * A category is looked up by its key in a URL. The key is ours, it never
 * changes, and it reads the same whatever language the catalogue is being
 * browsed in.
 */
#[RouteKey('key')]
#[Fillable([
    'key',
    'name_translation_key',
    'description_translation_key',
    'icon',
    'parent_id',
    'position',
    'is_active',
    'listing_count',
])]
class MarketplaceCategory extends Model
{
    /** @use HasFactory<MarketplaceCategoryFactory> */
    use HasFactory;

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
            'is_active' => 'boolean',
            'listing_count' => 'integer',
        ];
    }

    /**
     * Get the category this one sits under, which a top level one has none of.
     *
     * @return BelongsTo<MarketplaceCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'parent_id');
    }

    /**
     * Get the categories sitting under this one, in the order they are listed.
     * The tree is one level deep at most: deeper ones are how a catalogue
     * becomes unbrowsable again by a different route.
     *
     * @return HasMany<MarketplaceCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MarketplaceCategory::class, 'parent_id')->orderBy('position');
    }

    /**
     * Get everything filed under the category, whatever state it is in.
     * `listing_count` is what says how many of them are public without counting
     * them.
     *
     * @return HasMany<MarketplaceListing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class);
    }

    /**
     * Get what the category is called, in the language of whoever is looking.
     * There is no stored name to fall back from: the list is ours, so every one
     * of its words is translated rather than written once by somebody.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => __($this->name_translation_key),
        );
    }

    /**
     * Get what belongs in the category, in the language of whoever is looking.
     *
     * @return Attribute<string, never>
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (): string => __($this->description_translation_key),
        );
    }
}
