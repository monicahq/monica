<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionInsuranceRecord;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionInsuranceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_one_record(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'policy_number' => 'SAB-2026-0042']);

        MonicaServer::actingAs($user)->tool(GetPossessionInsuranceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'insurance_record' => $record->uuid,
        ])
            ->assertOk()
            ->assertSee('SAB-2026-0042');
    }

    #[Test]
    public function it_refuses_a_record_of_another_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        MonicaServer::actingAs($user)->tool(GetPossessionInsuranceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'insurance_record' => $record->uuid,
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
