<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionTransaction;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionTransaction::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'type' => 'purchase',
            'counterparty' => 'Dundies Inc.',
            'amount' => 40,
            'currency' => 'usd',
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Dundies Inc.');

        $this->assertDatabaseHas('possession_transactions', [
            'possession_id' => $dundie->id,
            'type' => 'purchase',
            'amount' => '40.00',
        ]);
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionTransaction::class, [
            'vault' => $vault->slug,
            'possession' => Possession::factory()->create()->uuid,
            'type' => 'purchase',
        ])->assertHasErrors();
    }
}
