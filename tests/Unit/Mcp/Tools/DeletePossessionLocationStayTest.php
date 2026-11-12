<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionLocationStay;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionLocationStayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_stay(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionLocationStay::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'stay' => $stay->uuid,
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($stay);
    }
}
