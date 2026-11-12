<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionProvenanceEventControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'type',
                'group',
                'title',
                'date_precision',
                'occurred_on',
                'from',
                'to',
                'location',
                'description',
                'reference_number',
                'source_url',
                'is_verified',
                'transaction_id',
                'created_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_records_an_event(): void
    {
        Queue::fake();

        [$user, $vault, $press] = $this->schruteFarms();
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $press->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance", [
            'type' => 'acquisition',
            'date_precision' => 'month',
            'occurred_on' => '1921-06',
            'to' => 'Dwight Schrute Sr.',
            'transaction_id' => $purchase->uuid,
            'is_verified' => true,
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.group', 'ownership')
            ->assertJsonPath('data.attributes.occurred_on', '1921-06')
            ->assertJsonPath('data.attributes.transaction_id', $purchase->uuid)
            ->assertJsonPath('data.attributes.is_verified', true);
    }

    #[Test]
    public function it_refuses_a_date_that_does_not_match_its_precision(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance", [
            'type' => 'origin',
            'date_precision' => 'year',
            'occurred_on' => '1921-06-01',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['occurred_on']);
    }

    #[Test]
    public function it_lists_the_events_of_the_possession_only_oldest_first(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'occurred_on' => '2005-03-24', 'title' => 'Second']);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'occurred_on' => '1921-01-01', 'title' => 'First']);
        PossessionProvenanceEvent::factory()->create(['title' => 'Elsewhere']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.title', 'First')
            ->assertJsonPath('data.1.attributes.title', 'Second');
    }

    #[Test]
    public function it_shows_an_event(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance/{$event->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure);
    }

    #[Test]
    public function it_changes_only_the_fields_it_is_sent(): void
    {
        Queue::fake();

        [$user, $vault, $press] = $this->schruteFarms();
        $event = PossessionProvenanceEvent::factory()->create([
            'possession_id' => $press->id,
            'date_precision' => PossessionProvenanceDatePrecisionEnum::Year,
            'occurred_on' => '1921-01-01',
            'location' => 'Honesdale',
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance/{$event->uuid}", [
            'is_verified' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.occurred_on', '1921')
            ->assertJsonPath('data.attributes.location', 'Honesdale')
            ->assertJsonPath('data.attributes.is_verified', true);
    }

    #[Test]
    public function it_deletes_an_event(): void
    {
        Queue::fake();

        [$user, $vault, $press] = $this->schruteFarms();
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance/{$event->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($event);
    }

    #[Test]
    public function it_answers_not_found_for_an_event_of_another_possession(): void
    {
        [$user, $vault, $press] = $this->schruteFarms();
        $event = PossessionProvenanceEvent::factory()->create([
            'possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$press->uuid}/provenance/{$event->uuid}")
            ->assertNotFound();
    }

    /** @return array{User, Vault, Possession} */
    private function schruteFarms(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press'])];
    }
}
