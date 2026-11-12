<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionMaintenanceRecord;
use App\Models\Possession;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionMaintenanceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_record(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'title' => 'Re-plating']);

        MonicaServer::actingAs($user)->tool(GetPossessionMaintenanceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'maintenance_record' => $record->uuid,
        ])
            ->assertOk()
            ->assertSee('Re-plating');
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
