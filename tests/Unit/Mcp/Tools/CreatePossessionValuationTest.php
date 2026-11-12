<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionValuation;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionValuationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_valuation(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();

        MonicaServer::actingAs($user)->tool(CreatePossessionValuation::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'type' => 'appraisal',
            'valued_on' => '2026-03-14',
            'amount' => 650,
            'currency' => 'usd',
            'valuer' => 'Scranton Jewelers',
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Scranton Jewelers');

        $this->assertDatabaseHas('possession_valuations', [
            'possession_id' => $dundie->id,
            'type' => 'appraisal',
            'amount' => '650.00',
            'confidence' => 'unknown',
        ]);
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
