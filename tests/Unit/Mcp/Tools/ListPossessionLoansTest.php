<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionLoans;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionLoansTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_loans_of_the_possession_only(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id, 'purpose' => 'Beet harvest party']);
        PossessionLoan::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id, 'purpose' => 'Dinner party']);

        MonicaServer::actingAs($user)->tool(ListPossessionLoans::class, [
            'vault' => $vault->slug,
            'possession' => $mug->uuid,
        ])
            ->assertOk()
            ->assertSee($loan->uuid)
            ->assertSee('Beet harvest party')
            ->assertDontSee('Dinner party');
    }
}
