<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionTransaction;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_sent(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'counterparty' => 'Dundies Inc.', 'shipping' => '60.00']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionTransaction::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'transaction' => $purchase->uuid,
            'shipping' => 6,
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $purchase->refresh();
        $this->assertSame('6.00', $purchase->shipping);
        $this->assertSame('Dundies Inc.', $purchase->counterparty);
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(UpdatePossessionTransaction::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'transaction' => PossessionTransaction::factory()->create()->uuid,
        ])->assertHasErrors();
    }
}
