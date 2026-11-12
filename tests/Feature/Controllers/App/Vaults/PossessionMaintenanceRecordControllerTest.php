<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionMaintenanceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_maintenance_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'title' => 'Re-plating']);

        $this->actingAs($user)->get(route('possessions.maintenanceRecords.index', [$vault, $dundie]))
            ->assertOk()
            ->assertSee('Re-plating')
            ->assertSee('Maintenance record')
            ->assertSee('aria-current="page"', escape: false)
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function it_records_a_maintenance_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('possessions.maintenanceRecords.create', [$vault, $dundie]), [
            'type' => 'restoration',
            'title' => 'Re-plating',
            'performed_on' => '2026-03-12',
            'cost' => '40',
            'currency' => 'usd',
            'condition_before' => $worn->uuid,
            'condition_after' => '',
        ])
            ->assertRedirect(route('possessions.maintenanceRecords.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_maintenance_records', [
            'possession_id' => $dundie->id,
            'title' => 'Re-plating',
            'cost' => '40.00',
            'condition_before_id' => $worn->id,
            'condition_after_id' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_record_without_a_title(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.maintenanceRecords.create', [$vault, $dundie]), [
            'type' => 'repair',
            'performed_on' => '2026-03-12',
        ])->assertSessionHasErrors('title');

        $this->assertDatabaseCount('possession_maintenance_records', 0);
    }

    #[Test]
    public function it_shows_the_action_refusal_beside_the_field(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.maintenanceRecords.create', [$vault, $dundie]), [
            'type' => 'repair',
            'title' => 'New gasket',
            'performed_on' => '2026-03-12',
            'next_due_on' => '2026-01-01',
        ])->assertSessionHasErrors('next_due_on');
    }

    #[Test]
    public function it_shows_the_filled_form_to_edit_a_record(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'title' => 'Reframing']);

        $this->actingAs($user)->get(route('possessions.maintenanceRecords.edit', [$vault, $dundie, $record]))
            ->assertOk()
            ->assertSee('Edit maintenance record')
            ->assertSee('value="Reframing"', escape: false);
    }

    #[Test]
    public function it_changes_a_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->put(route('possessions.maintenanceRecords.update', [$vault, $dundie, $record]), [
            'type' => 'conservation',
            'title' => 'Reframing',
            'performed_on' => '2026-04-02',
            'cost' => '45',
            'currency' => 'usd',
        ])
            ->assertRedirect(route('possessions.maintenanceRecords.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertSame('Reframing', $record->refresh()->title);
        $this->assertSame('45.00', $record->cost);
    }

    #[Test]
    public function it_deletes_a_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->delete(route('possessions.maintenanceRecords.destroy', [$vault, $dundie, $record]))
            ->assertRedirect(route('possessions.maintenanceRecords.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertModelMissing($record);
    }

    #[Test]
    public function it_answers_not_found_for_a_record_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $teapot = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $teapot->id]);

        $this->actingAs($user)->get(route('possessions.maintenanceRecords.edit', [$vault, $dundie, $record]))
            ->assertNotFound();
    }
}
