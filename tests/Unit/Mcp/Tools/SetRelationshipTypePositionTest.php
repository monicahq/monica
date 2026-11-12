<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetRelationshipTypePosition;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetRelationshipTypePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_relationship_type_within_its_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $attributes = ['account_id' => $user->account_id, 'relationship_category_id' => $category->id];
        $boss = RelationshipType::factory()->create([...$attributes, 'name' => 'Boss', 'position' => 1]);
        $deskmate = RelationshipType::factory()->create([...$attributes, 'name' => 'Deskmate', 'position' => 2]);
        $rival = RelationshipType::factory()->create([...$attributes, 'name' => 'Rival', 'position' => 3]);

        MonicaServer::actingAs($user)->tool(SetRelationshipTypePosition::class, [
            'relationship_type' => $rival->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(
            [$rival->uuid, $boss->uuid, $deskmate->uuid],
            $category->types()->orderBy('position')->pluck('uuid')->all(),
        );
    }
}
