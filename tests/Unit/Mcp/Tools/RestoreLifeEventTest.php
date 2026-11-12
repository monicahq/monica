<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Actions\CreateLifeEvent;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\RestoreLifeEvent;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_restores_a_deleted_life_event(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Booze cruise'),
            happenedOn: CarbonImmutable::parse('2026-01-15'),
        )->execute();
        $lifeEvent->delete();

        MonicaServer::actingAs($user)->tool(RestoreLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertNotSoftDeleted($lifeEvent);
    }

    #[Test]
    public function it_refuses_a_life_event_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(RestoreLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => '7c1e5a92-4b3d-4f80-a6e2-93d0b1c4f857',
        ])->assertHasErrors();
    }
}
