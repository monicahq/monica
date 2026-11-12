<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionProvenanceEvent;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_event(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'from' => 'Mose Schrute']);

        MonicaServer::actingAs($user)->tool(GetPossessionProvenanceEvent::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'event' => $event->uuid,
        ])
            ->assertOk()
            ->assertSee('Mose Schrute');
    }

    #[Test]
    public function it_says_when_the_event_does_not_exist(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $elsewhere = PossessionProvenanceEvent::factory()->create();

        MonicaServer::actingAs($user)->tool(GetPossessionProvenanceEvent::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'event' => $elsewhere->uuid,
        ])->assertHasErrors(['That provenance event does not exist.']);
    }
}
