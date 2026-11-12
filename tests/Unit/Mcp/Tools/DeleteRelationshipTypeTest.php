<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteRelationshipType;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_relationship_type_nobody_uses(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteRelationshipType::class, ['relationship_type' => $type->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($type);
    }

    #[Test]
    public function it_refuses_a_relationship_type_in_use(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);
        Relationship::factory()->create(['vault_id' => $vault->id, 'relationship_type_id' => $type->id]);

        EricaServer::actingAs($user)->tool(DeleteRelationshipType::class, ['relationship_type' => $type->uuid])
            ->assertHasErrors(['Relationships are written with this type, so it stays.']);

        $this->assertNotSoftDeleted($type);
    }
}
