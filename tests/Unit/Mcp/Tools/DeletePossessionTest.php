<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossession;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(DeletePossession::class, ['vault' => $vault->uuid, 'possession' => $sebring->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($sebring);
    }
}
