<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionProvenanceEvent;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_event_linked_to_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $press->id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionProvenanceEvent::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'type' => 'origin',
            'date_precision' => 'circa',
            'occurred_on' => '1921',
            'location' => 'Honesdale',
            'transaction' => $purchase->uuid,
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Honesdale');

        $this->assertDatabaseHas('possession_provenance_events', [
            'possession_id' => $press->id,
            'possession_transaction_id' => $purchase->id,
            'type' => 'origin',
            'date_precision' => 'circa',
            'is_verified' => false,
        ]);
    }
}
