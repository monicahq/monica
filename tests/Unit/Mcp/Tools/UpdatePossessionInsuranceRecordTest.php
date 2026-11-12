<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionInsuranceRecord;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionInsuranceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_is_sent(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionInsuranceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'insurance_record' => $record->uuid,
            'status' => 'expired',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('possession_insurance_records', ['id' => $record->id, 'status' => 'expired', 'insurer' => 'Sabre Insurance']);
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
