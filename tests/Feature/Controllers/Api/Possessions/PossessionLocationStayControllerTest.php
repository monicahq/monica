<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLocationStayControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'location_id',
                'location_name',
                'arrived_on',
                'left_on',
                'is_current',
                'reason',
                'note',
                'created_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_moves_a_possession(): void
    {
        Queue::fake();

        [$user, $vault, $press] = $this->schruteFarms();
        $cellar = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Cellar']);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays", [
            'location_id' => $cellar->uuid,
            'moved_on' => '2024-06-04',
            'reason' => 'Harvest season',
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.location_id', $cellar->uuid)
            ->assertJsonPath('data.attributes.location_name', 'Cellar')
            ->assertJsonPath('data.attributes.arrived_on', '2024-06-04')
            ->assertJsonPath('data.attributes.is_current', true);
    }

    #[Test]
    public function it_refuses_a_move_to_where_it_already_is(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays", [
            'location_id' => $stay->location->uuid,
            'moved_on' => '2024-06-04',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('location_id');
    }

    #[Test]
    public function it_lists_the_stays_of_the_possession_only_oldest_first(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        $newer = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'arrived_on' => '2024-06-04']);
        $older = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'arrived_on' => '2019-11-08', 'left_on' => '2024-06-04']);
        PossessionLocationStay::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $older->uuid)
            ->assertJsonPath('data.1.id', $newer->uuid);
    }

    #[Test]
    public function it_shows_a_stay(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays/{$stay->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.id', $stay->uuid);
    }

    #[Test]
    public function it_changes_only_the_fields_it_is_sent(): void
    {
        Queue::fake();

        [$user, $vault, $press] = $this->schruteFarms();
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'arrived_on' => '2019-11-08', 'left_on' => '2024-06-04', 'reason' => 'After the harvest']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays/{$stay->uuid}", [
            'note' => 'Mose remembers it well.',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.note', 'Mose remembers it well.')
            ->assertJsonPath('data.attributes.reason', 'After the harvest')
            ->assertJsonPath('data.attributes.arrived_on', '2019-11-08')
            ->assertJsonPath('data.attributes.left_on', '2024-06-04');
    }

    #[Test]
    public function it_deletes_a_stay(): void
    {
        Queue::fake();

        [$user, $vault, $press] = $this->schruteFarms();
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays/{$stay->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($stay);
    }

    #[Test]
    public function it_answers_not_found_for_a_stay_of_another_possession(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        $stay = PossessionLocationStay::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/location-stays/{$stay->uuid}")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function schruteFarms(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press'])];
    }
}
