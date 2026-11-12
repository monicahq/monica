<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionMaintenanceRecord;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionMaintenanceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_maintenance_record(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn']);

        MonicaServer::actingAs($user)->tool(CreatePossessionMaintenanceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'maintenance_type' => 'restoration',
            'title' => 'Re-plating',
            'performed_on' => '2026-03-12',
            'condition_before' => $worn->uuid,
        ])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Re-plating');

        $this->assertDatabaseHas('possession_maintenance_records', [
            'possession_id' => $dundie->id,
            'title' => 'Re-plating',
            'condition_before_id' => $worn->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_condition_of_another_account(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $condition = PossessionCondition::factory()->create();

        MonicaServer::actingAs($user)->tool(CreatePossessionMaintenanceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'maintenance_type' => 'repair',
            'title' => 'New gasket',
            'performed_on' => '2026-03-12',
            'condition_after' => $condition->uuid,
        ])->assertHasErrors();

        $this->assertDatabaseCount('possession_maintenance_records', 0);
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
