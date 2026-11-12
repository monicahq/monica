<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\PossessionTransaction;
use App\Models\PossessionType;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'description',
                'identifier',
                'status',
                'quantity',
                'is_featured',
                'position',
                'possession_type' => ['id', 'name', 'colour'],
                'location',
                'values',
                'acquisition' => ['way', 'acquired_from', 'acquired_on', 'transaction_id', 'declared_value'],
                'current_value',
                'tags',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_possessions_of_the_vault_by_name_and_nothing_else(): void
    {
        [$user, $vault] = $this->scranton();
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Stamford stapler']);
        Possession::factory()->create(['name' => 'Wernham Hogg mug']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Chrysler Sebring');
        $response->assertJsonPath('data.1.attributes.name', 'World’s Best Boss mug');
    }

    #[Test]
    public function it_narrows_the_list_by_type_status_and_featured(): void
    {
        [$user, $vault] = $this->scranton();
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id, 'name' => 'Chrysler Sebring', 'status' => 'sold']);
        Possession::factory()->featured(1)->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions?type={$car->uuid}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Chrysler Sebring');
        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions?status=sold")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Chrysler Sebring');
        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions?featured=1")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'World’s Best Boss mug');
    }

    #[Test]
    public function it_narrows_the_list_to_a_location_and_the_locations_inside_it(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Paper shredder', 'current_location_id' => $office->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet cart', 'current_location_id' => $annex->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Forklift', 'current_location_id' => $warehouse->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions?location={$office->uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Beet cart')
            ->assertJsonPath('data.1.attributes.name', 'Paper shredder');
    }

    #[Test]
    public function it_refuses_to_narrow_the_list_to_a_location_of_another_account(): void
    {
        [$user, $vault] = $this->scranton();
        $utica = Location::factory()->create(['name' => 'Utica branch']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions?location={$utica->uuid}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['location']);
    }

    #[Test]
    public function it_lists_compact_possessions_with_their_current_value(): void
    {
        [$user, $vault] = $this->scranton();
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring', 'declared_value' => '9000.00', 'declared_value_currency' => 'usd']);
        PossessionValuation::factory()->create(['possession_id' => $sebring->id, 'amount' => '7500.00', 'currency' => 'usd']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions?compact=1")
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => [[
                    'type',
                    'id',
                    'attributes' => ['name', 'status', 'possession_type' => ['id', 'name'], 'location', 'current_value' => ['amount', 'currency', 'source']],
                    'links' => ['self'],
                ]],
                'meta' => ['page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.attributes.current_value', ['amount' => '7500.00', 'currency' => 'usd', 'source' => 'valuation']);
    }

    #[Test]
    public function the_current_value_falls_back_to_the_declared_value(): void
    {
        [$user, $vault] = $this->scranton();
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'declared_value' => '9000.00', 'declared_value_currency' => 'usd']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.current_value', ['amount' => '9000.00', 'currency' => 'usd', 'source' => 'declared_value']);
    }

    #[Test]
    public function it_shows_a_possession(): void
    {
        [$user, $vault] = $this->scranton();
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$possession->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.name', 'Chrysler Sebring');
    }

    #[Test]
    public function it_does_not_find_a_possession_through_another_vault(): void
    {
        [$user, $vault] = $this->scranton();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$possession->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_records_a_possession(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $car = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Car']);
        $parkingLot = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton parking lot']);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions", [
            'name' => 'Chrysler Sebring',
            'type_id' => $car->uuid,
            'location_id' => $parkingLot->uuid,
            'identifier' => '1C3EL55R',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.status', 'owned');
        $response->assertJsonPath('data.attributes.quantity', 1);
        $response->assertJsonPath('data.attributes.possession_type.name', 'Car');
        $response->assertJsonPath('data.attributes.location.name', 'Scranton parking lot');
    }

    #[Test]
    public function it_records_how_a_possession_was_acquired_and_its_tags(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions", [
            'name' => 'Porcelain cat',
            'type_id' => PossessionType::factory()->create(['account_id' => $user->account_id])->uuid,
            'declared_value' => 120,
            'declared_value_currency' => 'usd',
            'tags' => ['insured', 'fragile'],
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.acquisition.way', null)
            ->assertJsonPath('data.attributes.acquisition.declared_value.amount', '120.00')
            ->assertJsonPath('data.attributes.acquisition.declared_value.currency', 'usd')
            ->assertJsonPath('data.attributes.tags.0.name', 'fragile')
            ->assertJsonPath('data.attributes.tags.1.name', 'insured');
    }

    #[Test]
    public function it_refuses_the_acquisition_it_no_longer_holds_and_a_value_without_currency(): void
    {
        [$user, $vault] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions", [
            'name' => 'Porcelain cat',
            'type_id' => PossessionType::factory()->create(['account_id' => $user->account_id])->uuid,
            'acquisition' => 'gift',
            'acquired_on' => '2024-12-25',
            'declared_value' => 120,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['acquisition', 'acquired_on', 'declared_value_currency']);
    }

    #[Test]
    public function it_reads_the_acquisition_from_the_acquisition_transaction(): void
    {
        [$user, $vault] = $this->scranton();
        $cat = Possession::factory()->create(['vault_id' => $vault->id]);
        $gift = PossessionTransaction::factory()->create([
            'possession_id' => $cat->id,
            'type' => PossessionTransactionTypeEnum::GiftReceived,
            'occurred_on' => '2024-12-25',
            'counterparty' => 'Angela Martin',
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$cat->uuid}")
            ->assertOk()
            ->assertJsonPath('data.attributes.acquisition.way', 'gift_received')
            ->assertJsonPath('data.attributes.acquisition.acquired_from.name', 'Angela Martin')
            ->assertJsonPath('data.attributes.acquisition.acquired_on', '2024-12-25')
            ->assertJsonPath('data.attributes.acquisition.transaction_id', $gift->uuid);
    }

    #[Test]
    public function a_change_replaces_everything_so_tags_left_out_are_cleared(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $possession->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id]));

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$possession->uuid}", ['name' => 'Chili pot'])
            ->assertOk()
            ->assertJsonCount(0, 'data.attributes.tags');
    }

    #[Test]
    public function it_refuses_a_type_of_another_account_and_what_does_not_fit(): void
    {
        [$user, $vault] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions", [
            'name' => '',
            'type_id' => PossessionType::factory()->create()->uuid,
            'status' => 'borrowed',
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type_id', 'status', 'quantity']);
    }

    #[Test]
    public function it_changes_a_possession(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Sebring']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$possession->uuid}", [
            'name' => 'Chrysler Sebring',
            'status' => 'sold',
            'is_featured' => true,
        ])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.status', 'sold')
            ->assertJsonPath('data.attributes.position', 1);

        $this->assertSame('Chrysler Sebring', $possession->refresh()->name);
    }

    #[Test]
    public function it_deletes_a_possession(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$possession->uuid}")->assertNoContent();

        $this->assertModelMissing($possession);
    }

    /** @return array{0: User, 1: Vault} */
    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
