<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_categories_under_it_in_order(): void
    {
        $health = MarketplaceCategory::factory()->create();
        MarketplaceCategory::factory()->under($health)->create(['position' => 2]);
        MarketplaceCategory::factory()->under($health)->create(['position' => 1]);

        $this->assertEquals([1, 2], $health->children->pluck('position')->all());
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
}
