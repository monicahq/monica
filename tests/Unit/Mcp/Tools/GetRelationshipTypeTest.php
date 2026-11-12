<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetRelationshipType;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_relationship_type(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetRelationshipType::class, ['relationship_type' => $type->uuid])
            ->assertOk()
            ->assertSee('Godparent / Godchild')
            ->assertSee($type->relationshipCategory->uuid);
    }

    #[Test]
    public function it_reports_a_relationship_type_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create();

        EricaServer::actingAs($user)->tool(GetRelationshipType::class, ['relationship_type' => $type->uuid])
            ->assertHasErrors(['That relationship type does not exist.']);
    }
}
