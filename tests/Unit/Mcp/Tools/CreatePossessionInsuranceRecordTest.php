<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionInsuranceRecord;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionInsuranceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_insurance_record(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();

        MonicaServer::actingAs($user)->tool(CreatePossessionInsuranceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'insurer' => 'Sabre Insurance',
            'status' => 'active',
            'insured_value' => 500,
            'currency' => 'usd',
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Sabre Insurance');

        $this->assertDatabaseHas('possession_insurance_records', [
            'possession_id' => $dundie->id,
            'insurer' => 'Sabre Insurance',
            'insured_value' => '500.00',
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
