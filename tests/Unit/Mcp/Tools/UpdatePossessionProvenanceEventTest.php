<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionProvenanceEvent;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_sent(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'location' => 'Honesdale']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionProvenanceEvent::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'event' => $event->uuid,
            'is_verified' => true,
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertTrue($event->is_verified);
        $this->assertSame('Honesdale', $event->location);
        $this->assertSame('2005-03-24', $event->occurred_on?->toDateString());
    }
}
