<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionLocationStay;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionLocationStayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_stay(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'reason' => 'After the harvest']);

        MonicaServer::actingAs($user)->tool(GetPossessionLocationStay::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'stay' => $stay->uuid,
        ])
            ->assertOk()
            ->assertSee('After the harvest');
    }
}
