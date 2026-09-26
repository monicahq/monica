<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Jobs\LogUserAction;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetRelationshipTypeActivation;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetRelationshipTypeActivationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_switches_a_relationship_type_off(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetRelationshipTypeActivation::class, [
            'relationship_type' => $type->uuid,
            'is_active' => false,
        ])->assertOk()->assertHasNoErrors();

        $this->assertFalse($type->refresh()->is_active);
    }

    #[Test]
    public function it_switches_a_relationship_type_on(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->inactive()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetRelationshipTypeActivation::class, [
            'relationship_type' => $type->uuid,
            'is_active' => true,
        ])->assertOk()->assertHasNoErrors();

        $this->assertTrue($type->refresh()->is_active);
    }

    #[Test]
    public function it_journals_nothing_when_the_type_is_already_in_that_state(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetRelationshipTypeActivation::class, [
            'relationship_type' => $type->uuid,
            'is_active' => true,
        ])->assertOk()->assertHasNoErrors();

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.relationshipTypes.activation.update', app(SetRelationshipTypeActivation::class)->mirrors());
    }
}
