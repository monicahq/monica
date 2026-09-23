<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\EntityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key(): void
    {
        $entityType = EntityType::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $entityType->uuid,
        );
        $this->assertIsInt($entityType->id);
    }

    #[Test]
    public function it_is_found_in_a_url_by_its_slug(): void
    {
        $entityType = EntityType::factory()->create(['slug' => 'wine-bottles']);

        $this->assertEquals('slug', $entityType->getRouteKeyName());
        $this->assertEquals('wine-bottles', $entityType->getRouteKey());
    }

    #[Test]
    public function it_keeps_a_singular_and_a_plural_name_of_its_own(): void
    {
        $entityType = EntityType::factory()->create([
            'name_singular' => 'Wine bottle',
            'name_plural' => 'Wine bottles',
        ]);

        $this->assertEquals('Wine bottle', $entityType->name_singular);
        $this->assertEquals('Wine bottles', $entityType->name_plural);
    }

    #[Test]
    public function it_is_active_and_holds_nothing_to_begin_with(): void
    {
        $entityType = EntityType::factory()->create();

        $this->assertTrue($entityType->is_active);
        $this->assertSame(0, $entityType->entity_count);
    }
}
