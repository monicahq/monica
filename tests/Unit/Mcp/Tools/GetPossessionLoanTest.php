<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionLoan;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_loan(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id, 'purpose' => 'Beet harvest party']);

        MonicaServer::actingAs($user)->tool(GetPossessionLoan::class, [
            'vault' => $vault->slug,
            'possession' => $mug->uuid,
            'loan' => $loan->uuid,
        ])
            ->assertOk()
            ->assertSee('Beet harvest party');
    }

    #[Test]
    public function it_answers_that_a_loan_of_another_possession_does_not_exist(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        MonicaServer::actingAs($user)->tool(GetPossessionLoan::class, [
            'vault' => $vault->slug,
            'possession' => $mug->uuid,
            'loan' => $loan->uuid,
        ])->assertHasErrors(['That loan does not exist.']);
    }
}
