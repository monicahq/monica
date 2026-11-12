<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use Database\Seeders\MarketplaceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceCategorySeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_every_category_of_the_catalogue_in_place(): void
    {
        new MarketplaceCategorySeeder()->run();

        $this->assertCount(count(MarketplaceCategoryEnum::cases()), MarketplaceCategory::all());
        $this->assertDatabaseHas('marketplace_categories', [
            'key' => MarketplaceCategoryEnum::Health->value,
            'name_translation_key' => MarketplaceCategoryEnum::Health->label(),
            'icon' => MarketplaceCategoryEnum::Health->icon(),
            'position' => 1,
            'is_active' => true,
            'listing_count' => 0,
        ]);
    }

    #[Test]
    public function it_can_be_run_again_without_making_duplicates(): void
    {
        new MarketplaceCategorySeeder()->run();
        new MarketplaceCategorySeeder()->run();

        $this->assertCount(count(MarketplaceCategoryEnum::cases()), MarketplaceCategory::all());
    }

    #[Test]
    public function it_leaves_the_tally_and_the_state_of_a_category_alone(): void
    {
        new MarketplaceCategorySeeder()->run();

        $category = MarketplaceCategory::query()->where('key', MarketplaceCategoryEnum::Health->value)->firstOrFail();
        $category->listing_count = 12;
        $category->is_active = false;
        $category->save();

        new MarketplaceCategorySeeder()->run();

        $category->refresh();

        $this->assertEquals(12, $category->listing_count);
        $this->assertFalse($category->is_active);
    }
}
