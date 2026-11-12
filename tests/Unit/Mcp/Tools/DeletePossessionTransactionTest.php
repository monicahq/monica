<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionTransaction;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $fee = PossessionTransaction::factory()->create(['possession_id' => $dundie->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionTransaction::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'transaction' => $fee->uuid,
        ])
            ->assertOk();

        $this->assertModelMissing($fee);
    }
}
