<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetPossessionPosition;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetPossessionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_featured_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(SetPossessionPosition::class, ['vault' => $vault->uuid, 'possession' => $mug->uuid, 'position' => 1])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSame(1, $mug->refresh()->position);
        $this->assertSame(2, $sebring->refresh()->position);
    }
}
