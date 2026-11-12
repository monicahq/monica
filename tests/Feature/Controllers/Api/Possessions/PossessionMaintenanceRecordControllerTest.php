<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Enums\CurrencyEnum;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionMaintenanceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'maintenance_type',
                'title',
                'performed_by',
                'performed_on',
                'cost',
                'currency',
                'condition_before',
                'condition_after',
                'next_due_on',
                'is_overdue',
                'description',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_records_a_maintenance_record(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $worn = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn']);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records", [
            'maintenance_type' => 'restoration',
            'title' => 'Re-plating',
            'performed_on' => '2026-03-12',
            'cost' => 40,
            'currency' => 'usd',
            'condition_before_id' => $worn->uuid,
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.cost', '40.00')
            ->assertJsonPath('data.attributes.condition_before.name', 'Worn')
            ->assertJsonPath('data.attributes.condition_after', null);
    }

    #[Test]
    public function it_refuses_a_record_without_its_required_fields(): void
    {
        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records", [
            'title' => 'Re-plating',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['maintenance_type', 'performed_on']);
    }

    #[Test]
    public function it_answers_not_found_for_a_condition_of_another_account(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $condition = PossessionCondition::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records", [
            'maintenance_type' => 'repair',
            'title' => 'New gasket',
            'performed_on' => '2026-03-12',
            'condition_after_id' => $condition->uuid,
        ])->assertNotFound();
    }

    #[Test]
    public function it_lists_the_records_of_the_possession_only(): void
    {
        $this->travelTo('2026-10-27');

        [$user, $vault, $dundie] = $this->scranton();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'performed_on' => '2025-01-01', 'title' => 'Annual check']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id, 'performed_on' => '2026-03-12', 'title' => 'Re-plating', 'next_due_on' => '2026-09-01']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.title', 'Re-plating')
            ->assertJsonPath('data.0.attributes.is_overdue', true)
            ->assertJsonPath('data.1.attributes.is_overdue', false);
    }

    #[Test]
    public function it_shows_a_record(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records/{$record->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure);
    }

    #[Test]
    public function it_changes_only_the_fields_sent(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $likeNew = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Like new']);
        $record = PossessionMaintenanceRecord::factory()->create([
            'possession_id' => $dundie->id,
            'title' => 'Reframing',
            'cost' => '30.00',
            'currency' => CurrencyEnum::UsDollar,
            'condition_after_id' => $likeNew->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records/{$record->uuid}", [
            'cost' => 45,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.cost', '45.00')
            ->assertJsonPath('data.attributes.currency', 'usd')
            ->assertJsonPath('data.attributes.title', 'Reframing')
            ->assertJsonPath('data.attributes.condition_after.name', 'Like new');
    }

    #[Test]
    public function it_deletes_a_record(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records/{$record->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($record);
    }

    #[Test]
    public function it_answers_not_found_for_a_record_of_another_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/maintenance-records/{$record->uuid}")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award'])];
    }
}
