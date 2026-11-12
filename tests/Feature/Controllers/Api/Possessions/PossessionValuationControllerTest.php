<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionValuationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'type',
                'valued_on',
                'confidence',
                'amount',
                'currency',
                'is_current',
                'valuer',
                'method',
                'reference_number',
                'source_url',
                'note',
                'documents',
                'created_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_records_a_valuation(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/valuations", [
            'type' => 'appraisal',
            'valued_on' => '2026-03-14',
            'amount' => 650,
            'currency' => 'usd',
            'valuer' => 'Scranton Jewelers',
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.amount', '650.00')
            ->assertJsonPath('data.attributes.confidence', 'unknown')
            ->assertJsonPath('data.attributes.is_current', true);
    }

    #[Test]
    public function it_refuses_a_valuation_without_its_required_fields(): void
    {
        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/valuations", [
            'valuer' => 'Scranton Jewelers',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'valued_on', 'amount', 'currency']);
    }

    #[Test]
    public function it_lists_the_valuations_of_the_possession_only_newest_first(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2019-11-20', 'valuer' => 'Michael Scott']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2026-03-14', 'valuer' => 'Scranton Jewelers']);
        PossessionValuation::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/valuations")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.valuer', 'Scranton Jewelers')
            ->assertJsonPath('data.0.attributes.is_current', true)
            ->assertJsonPath('data.1.attributes.is_current', false);
    }

    #[Test]
    public function it_shows_a_valuation(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $valuation = PossessionValuation::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/valuations/{$valuation->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure);
    }

    #[Test]
    public function it_deletes_a_valuation(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $valuation = PossessionValuation::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/valuations/{$valuation->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($valuation);
    }

    #[Test]
    public function it_answers_not_found_for_a_valuation_of_another_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $valuation = PossessionValuation::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/valuations/{$valuation->uuid}")
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
