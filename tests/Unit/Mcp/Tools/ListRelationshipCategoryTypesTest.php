<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Tools\ListRelationshipCategoryTypes;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListRelationshipCategoryTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_a_category_by_position(): void
    {
        $user = User::factory()->create();
        $office = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $warehouse = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $office->id, 'name' => 'Rival', 'position' => 2]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $office->id, 'name' => 'Deskmate', 'position' => 1]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $warehouse->id, 'name' => 'Forklift partner']);

        $this->actingAs($user);

        $response = app(ListRelationshipCategoryTypes::class)->handle(new Request(['relationship_category' => $office->uuid]));

        $this->assertSame(
            ['Deskmate', 'Rival'],
            array_column(array_column(json_decode((string) $response->content(), true), 'attributes'), 'name'),
        );
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.relationshipCategories.types', app(ListRelationshipCategoryTypes::class)->mirrors());
    }
}
