<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionInsuranceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_insurance_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance']);

        $this->actingAs($user)->get(route('possessions.insuranceRecords.index', [$vault, $dundie]))
            ->assertOk()
            ->assertSee('Sabre Insurance')
            ->assertSee('Add an insurance record')
            ->assertSee('aria-current="page"', escape: false)
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function it_records_an_insurance_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.insuranceRecords.create', [$vault, $dundie]), [
            'insurer' => 'Sabre Insurance',
            'status' => 'active',
            'insured_value' => '500',
            'currency' => 'usd',
            'excess' => '50',
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'is_listed_individually' => '1',
        ])
            ->assertRedirect(route('possessions.insuranceRecords.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_insurance_records', [
            'possession_id' => $dundie->id,
            'insurer' => 'Sabre Insurance',
            'insured_value' => '500.00',
            'excess' => '50.00',
            'is_listed_individually' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_record_without_an_insured_value(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.insuranceRecords.create', [$vault, $dundie]), [
            'insurer' => 'Sabre Insurance',
            'status' => 'active',
            'currency' => 'usd',
        ])->assertSessionHasErrors('insured_value');

        $this->assertDatabaseCount('possession_insurance_records', 0);
    }

    #[Test]
    public function it_shows_the_action_refusal_beside_the_field(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.insuranceRecords.create', [$vault, $dundie]), [
            'insurer' => 'Sabre Insurance',
            'status' => 'active',
            'insured_value' => '500',
            'currency' => 'usd',
            'starts_on' => '2027-01-01',
            'ends_on' => '2026-01-01',
        ])->assertSessionHasErrors('ends_on');
    }

    #[Test]
    public function it_shows_the_filled_form_to_edit_a_record(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'policy_number' => 'SAB-0001']);

        $this->actingAs($user)->get(route('possessions.insuranceRecords.edit', [$vault, $dundie, $record]))
            ->assertOk()
            ->assertSee('Edit insurance record')
            ->assertSee('value="SAB-0001"', escape: false);
    }

    #[Test]
    public function it_changes_a_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->put(route('possessions.insuranceRecords.update', [$vault, $dundie, $record]), [
            'insurer' => 'Sabre Insurance',
            'status' => 'expired',
            'insured_value' => '450',
            'currency' => 'usd',
            'policy_number' => 'SAB-2026-0042',
        ])
            ->assertRedirect(route('possessions.insuranceRecords.index', [$vault, $dundie]))
            ->assertSessionHas('status');

        $this->assertSame('SAB-2026-0042', $record->refresh()->policy_number);
        $this->assertFalse($record->is_listed_individually);
    }

    #[Test]
    public function it_deletes_a_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id]);

        $this->actingAs($user)->delete(route('possessions.insuranceRecords.destroy', [$vault, $dundie, $record]))
            ->assertRedirect(route('possessions.insuranceRecords.index', [$vault, $dundie]))
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
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $teapot->id]);

        $this->actingAs($user)->get(route('possessions.insuranceRecords.edit', [$vault, $dundie, $record]))
            ->assertNotFound();
    }
}
