<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateRelationshipType;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->directed()->inactive()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipType::class, [
            'relationship_type' => $type->uuid,
            'role_b' => 'Godson',
        ])->assertOk()->assertHasNoErrors();

        $type->refresh();

        $this->assertSame('Godson', $type->role_b);
        $this->assertSame('Godparent', $type->role_a);
        $this->assertFalse($type->is_symmetric);
        $this->assertFalse($type->is_active);
    }

    #[Test]
    public function it_moves_a_type_to_another_category(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton branch']);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipType::class, [
            'relationship_type' => $type->uuid,
            'relationship_category_id' => $category->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame($category->id, $type->refresh()->relationship_category_id);
    }

    #[Test]
    public function it_refuses_turning_a_symmetric_type_directed_without_a_second_role(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipType::class, [
            'relationship_type' => $type->uuid,
            'is_symmetric' => false,
        ])->assertHasErrors();

        $this->assertTrue($type->refresh()->is_symmetric);
    }

    #[Test]
    public function it_refuses_changing_the_direction_of_a_type_in_use(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);
        Relationship::factory()->create(['vault_id' => $vault->id, 'relationship_type_id' => $type->id]);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipType::class, [
            'relationship_type' => $type->uuid,
            'is_symmetric' => true,
        ])->assertHasErrors();

        $this->assertFalse($type->refresh()->is_symmetric);
    }

    #[Test]
    public function it_refuses_a_name_another_type_of_the_account_uses(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'name' => 'Deskmate']);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id, 'name' => 'Rival']);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipType::class, [
            'relationship_type' => $type->uuid,
            'name' => 'Deskmate',
        ])->assertHasErrors();

        $this->assertSame('Rival', $type->refresh()->name);
    }
}
