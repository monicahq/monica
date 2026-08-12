<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sits_under_another_category(): void
    {
        $health = MarketplaceCategory::factory()->create();
        $followUp = MarketplaceCategory::factory()->under($health)->create();

        $this->assertTrue($followUp->parent()->exists());
        $this->assertEquals($health->id, $followUp->parent->id);
    }

    #[Test]
    public function it_lists_the_categories_under_it_in_order(): void
    {
        $health = MarketplaceCategory::factory()->create();
        MarketplaceCategory::factory()->under($health)->create(['position' => 2]);
        MarketplaceCategory::factory()->under($health)->create(['position' => 1]);

        $this->assertEquals([1, 2], $health->children->pluck('position')->all());
    }

    #[Test]
    public function it_has_many_listings(): void
    {
        $category = MarketplaceCategory::factory()->create();
        MarketplaceListing::factory()->create(['marketplace_category_id' => $category->id]);

        $this->assertTrue($category->listings()->exists());
    }

    #[Test]
    public function it_reads_its_name_and_description_in_the_language_of_whoever_is_looking(): void
    {
        $category = MarketplaceCategory::factory()->create([
            'name_translation_key' => MarketplaceCategoryEnum::Animals->label(),
            'description_translation_key' => MarketplaceCategoryEnum::Animals->description(),
        ]);

        $this->assertEquals(__(MarketplaceCategoryEnum::Animals->label()), $category->name);
        $this->assertEquals(__(MarketplaceCategoryEnum::Animals->description()), $category->description);
    }

    #[Test]
    public function it_is_reached_by_its_key(): void
    {
        $category = MarketplaceCategory::factory()->create(['key' => 'health']);

        $this->assertEquals('key', $category->getRouteKeyName());
        $this->assertEquals('health', $category->getRouteKey());
    }

    #[Test]
    public function it_takes_new_publications_until_it_is_deactivated(): void
    {
        $this->assertTrue(MarketplaceCategory::factory()->create()->is_active);
        $this->assertFalse(MarketplaceCategory::factory()->inactive()->create()->is_active);
    }
}
