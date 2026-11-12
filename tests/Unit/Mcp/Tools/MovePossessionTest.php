<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\MovePossession;
use App\Models\Location;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MovePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $cellar = Location::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => null]);

        MonicaServer::actingAs($user)->tool(MovePossession::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'location' => $cellar->uuid,
            'moved_on' => '2024-06-04',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($cellar->id, $press->refresh()->current_location_id);
    }
}
