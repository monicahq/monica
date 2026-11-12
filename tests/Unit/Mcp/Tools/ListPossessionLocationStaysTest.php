<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionLocationStays;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionLocationStaysTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_stays_of_the_possession_only(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'reason' => 'After the harvest']);
        PossessionLocationStay::factory()->create(['reason' => 'Kept in the warehouse']);

        MonicaServer::actingAs($user)->tool(ListPossessionLocationStays::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
        ])
            ->assertOk()
            ->assertSee('After the harvest')
            ->assertDontSee('Kept in the warehouse');
    }
}
