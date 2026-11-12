<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionValuation;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionValuationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_valuation(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $valuation = PossessionValuation::factory()->create(['possession_id' => $dundie->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionValuation::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'valuation' => $valuation->uuid,
        ])->assertOk();

        $this->assertModelMissing($valuation);
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
