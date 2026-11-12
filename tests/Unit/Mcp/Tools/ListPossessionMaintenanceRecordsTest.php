<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionMaintenanceRecords;
use App\Models\Possession;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionMaintenanceRecordsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_records_of_a_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'title' => 'Re-plating']);

        MonicaServer::actingAs($user)->tool(ListPossessionMaintenanceRecords::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
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
