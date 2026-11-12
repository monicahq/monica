<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionTransaction;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_transaction_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'counterparty' => 'Dundies Inc.']);

        MonicaServer::actingAs($user)->tool(GetPossessionTransaction::class, [
            'vault' => $vault->uuid,
            'possession' => $dundie->uuid,
            'transaction' => $purchase->uuid,
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee($purchase->uuid)
            ->assertSee('Dundies Inc.');
    }

    #[Test]
    public function it_reports_a_transaction_of_another_possession_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id]);
        $lease = PossessionTransaction::factory()->create(['possession_id' => $sebring->id]);

        MonicaServer::actingAs($user)->tool(GetPossessionTransaction::class, [
            'vault' => $vault->uuid,
            'possession' => $dundie->uuid,
            'transaction' => $lease->uuid,
        ])
            ->assertHasErrors();
    }
}
