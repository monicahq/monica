<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Jobs\LogUserAction;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetEntityTypeActivation;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetEntityTypeActivationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_switches_an_entity_type_off(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetEntityTypeActivation::class, [
            'entity_type' => $entityType->uuid,
            'is_active' => false,
        ])->assertOk()->assertHasNoErrors();

        $this->assertFalse($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_switches_an_entity_type_on(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetEntityTypeActivation::class, [
            'entity_type' => $entityType->uuid,
            'is_active' => true,
        ])->assertOk()->assertHasNoErrors();

        $this->assertTrue($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_journals_nothing_when_the_type_is_already_in_that_state(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetEntityTypeActivation::class, [
            'entity_type' => $entityType->uuid,
            'is_active' => true,
        ])->assertOk()->assertHasNoErrors();

        Queue::assertNotPushed(LogUserAction::class);
    }
}
