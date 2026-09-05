<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use Illuminate\Database\Seeder;

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
