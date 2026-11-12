<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetLifeEvent;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_life_event(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Booze cruise']);

        MonicaServer::actingAs($user)->tool(GetLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee('Booze cruise');
    }

    #[Test]
    public function it_refuses_a_life_event_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $nashua = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $nashua->id]);

        MonicaServer::actingAs($user)->tool(GetLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
        ])->assertHasErrors();
    }
}
