<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionMaintenanceRecord;
use App\Models\Possession;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionMaintenanceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_the_fields_sent(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'title' => 'Reframing', 'performed_by' => 'Pam Beesly']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionMaintenanceRecord::class, [
            'vault' => $vault->slug,
            'possession' => $dundie->uuid,
            'maintenance_record' => $record->uuid,
            'title' => 'New frame',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $record->refresh();
        $this->assertSame('New frame', $record->title);
        $this->assertSame('Pam Beesly', $record->performed_by);
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id])];
    }
}
