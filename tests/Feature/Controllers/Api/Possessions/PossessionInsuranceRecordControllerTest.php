<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Enums\PossessionInsuranceStatusEnum;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionInsuranceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'insurer',
                'status',
                'is_current',
                'policy_number',
                'coverage',
                'insured_value',
                'excess',
                'currency',
                'starts_on',
                'ends_on',
                'is_listed_individually',
                'contact_name',
                'contact_email',
                'contact_phone',
                'note',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_records_an_insurance_record(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records", [
            'insurer' => 'Sabre Insurance',
            'status' => 'active',
            'insured_value' => 500,
            'currency' => 'usd',
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'is_listed_individually' => true,
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.insured_value', '500.00')
            ->assertJsonPath('data.attributes.is_current', true)
            ->assertJsonPath('data.attributes.is_listed_individually', true);
    }

    #[Test]
    public function it_refuses_a_record_without_its_required_fields(): void
    {
        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records", [
            'insurer' => 'Sabre Insurance',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'insured_value', 'currency']);
    }

    #[Test]
    public function it_lists_the_records_of_the_possession_only(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'status' => PossessionInsuranceStatusEnum::Expired, 'insurer' => 'Utica Assurance']);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance']);
        PossessionInsuranceRecord::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.insurer', 'Sabre Insurance')
            ->assertJsonPath('data.1.attributes.is_current', false);
    }

    #[Test]
    public function it_shows_a_record(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records/{$record->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure);
    }

    #[Test]
    public function it_changes_only_the_fields_sent(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance', 'policy_number' => 'SAB-0001', 'is_listed_individually' => true]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records/{$record->uuid}", [
            'policy_number' => 'SAB-2026-0042',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.policy_number', 'SAB-2026-0042')
            ->assertJsonPath('data.attributes.insurer', 'Sabre Insurance')
            ->assertJsonPath('data.attributes.is_listed_individually', true);
    }

    #[Test]
    public function it_deletes_a_record(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records/{$record->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($record);
    }

    #[Test]
    public function it_answers_not_found_for_a_record_of_another_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/insurance-records/{$record->uuid}")
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
