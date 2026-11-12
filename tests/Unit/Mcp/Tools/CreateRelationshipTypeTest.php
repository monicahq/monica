<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateRelationshipType;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_directed_relationship_type(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateRelationshipType::class, [
            'relationship_category_id' => $category->uuid,
            'name' => 'Regional manager',
            'role_a' => 'Regional manager',
            'role_b' => 'Salesman',
            'is_symmetric' => false,
        ])->assertOk()->assertHasNoErrors()->assertSee('Salesman');

        $this->assertDatabaseHas('relationship_types', [
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Regional manager',
            'role_a' => 'Regional manager',
            'role_b' => 'Salesman',
            'is_symmetric' => false,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_a_symmetric_type_whose_second_role_is_the_first(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateRelationshipType::class, [
            'relationship_category_id' => $category->uuid,
            'name' => 'Deskmate',
            'role_a' => 'Deskmate',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('relationship_types', [
            'account_id' => $user->account_id,
            'name' => 'Deskmate',
            'role_a' => 'Deskmate',
            'role_b' => 'Deskmate',
            'is_symmetric' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_directed_type_without_a_second_role(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateRelationshipType::class, [
            'relationship_category_id' => $category->uuid,
            'name' => 'Regional manager',
            'role_a' => 'Regional manager',
            'is_symmetric' => false,
        ])->assertHasErrors(['The role b field is required.']);

        $this->assertSame(0, RelationshipType::query()->count());
    }

    #[Test]
    public function it_refuses_a_name_another_type_of_the_account_uses(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Deskmate',
        ]);

        EricaServer::actingAs($user)->tool(CreateRelationshipType::class, [
            'relationship_category_id' => $category->uuid,
            'name' => 'Deskmate',
            'role_a' => 'Deskmate',
        ])->assertHasErrors(['Another type in this account already goes by that name.']);

        $this->assertSame(1, RelationshipType::query()->count());
    }

    #[Test]
    public function it_refuses_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create();

        EricaServer::actingAs($user)->tool(CreateRelationshipType::class, [
            'relationship_category_id' => $category->uuid,
            'name' => 'Deskmate',
            'role_a' => 'Deskmate',
        ])->assertHasErrors(['That relationship category does not exist.']);

        $this->assertSame(0, RelationshipType::query()->count());
    }
}
