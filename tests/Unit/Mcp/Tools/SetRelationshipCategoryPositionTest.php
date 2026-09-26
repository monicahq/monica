<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetRelationshipCategoryPosition;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetRelationshipCategoryPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_relationship_category(): void
    {
        $user = User::factory()->create();
        $office = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Office', 'position' => 1]);
        $warehouse = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse', 'position' => 2]);
        $annex = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Annex', 'position' => 3]);

        EricaServer::actingAs($user)->tool(SetRelationshipCategoryPosition::class, [
            'relationship_category' => $annex->uuid,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame(
            [$annex->uuid, $office->uuid, $warehouse->uuid],
            $user->account->relationshipCategories()->orderBy('position')->pluck('uuid')->all(),
        );
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.relationshipCategories.position.update', app(SetRelationshipCategoryPosition::class)->mirrors());
    }
}
