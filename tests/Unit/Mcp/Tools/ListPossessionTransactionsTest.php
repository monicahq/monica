<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionTransactions;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionTransactionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_transactions_of_the_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'counterparty' => 'Dundies Inc.']);
        PossessionTransaction::factory()->create(['counterparty' => 'Wernham Hogg']);

        MonicaServer::actingAs($user)->tool(ListPossessionTransactions::class, ['vault' => $vault->slug, 'possession' => $dundie->uuid])
            ->assertOk()
            ->assertSee('Dundies Inc.')
            ->assertDontSee('Wernham Hogg');
    }
}
