<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionValuation;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionValuationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_one_valuation(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $valuation = PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'reference_number' => 'APP-2026-0212']);

        MonicaServer::actingAs($user)->tool(GetPossessionValuation::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'valuation' => $valuation->uuid,
        ])
            ->assertOk()
            ->assertSee('APP-2026-0212');
    }

    #[Test]
    public function it_refuses_a_valuation_of_another_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $valuation = PossessionValuation::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        MonicaServer::actingAs($user)->tool(GetPossessionValuation::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'valuation' => $valuation->uuid,
        ])->assertHasErrors();
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
