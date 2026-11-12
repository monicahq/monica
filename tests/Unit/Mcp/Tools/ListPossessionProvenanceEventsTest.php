<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionProvenanceEvents;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionProvenanceEventsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_events_of_the_possession_only(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'title' => 'Built in Honesdale']);
        PossessionProvenanceEvent::factory()->create(['title' => 'Bought at Staples']);

        MonicaServer::actingAs($user)->tool(ListPossessionProvenanceEvents::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
        ])
            ->assertOk()
            ->assertSee('Built in Honesdale')
            ->assertDontSee('Bought at Staples');
    }
}
