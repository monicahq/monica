<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionProvenanceEvent;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionProvenanceEvent::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'event' => $event->uuid,
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($event);
    }
}
