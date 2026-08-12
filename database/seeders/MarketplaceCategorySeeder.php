<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use Illuminate\Database\Seeder;

/**
 * Put the categories of the catalogue in place.
 *
 * They are instance level data rather than something an account owns, so this
 * runs on deployment and not at sign up. It is written to be run again: the key
 * is what a category is recognised by, the tally of what is filed under it
 * belongs to the listings and is left alone, and a category somebody
 * deactivated stays deactivated.
 */
class MarketplaceCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (MarketplaceCategoryEnum::cases() as $position => $case) {
            $category = MarketplaceCategory::query()->firstOrNew(['key' => $case->value]);

            $category->name_translation_key = $case->label();
            $category->description_translation_key = $case->description();
            $category->icon = $case->icon();
            $category->position = $position + 1;
            $category->save();
        }
    }
}
