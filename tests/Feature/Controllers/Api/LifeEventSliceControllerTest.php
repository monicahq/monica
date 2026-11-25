<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventSliceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => ['*' => ['slice', 'count', 'total']],
        'meta' => ['total', 'today'],
    ];

    #[Test]
    public function it_counts_the_slices_under_the_contact_asked_for(): void
    {
        $this->travelTo('2026-11-22 10:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $this->lifeEventWith($vault, '2025-05-01', $dwight);
        $this->lifeEventWith($vault, '2025-06-01', $jim);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-event-slices", ['contact' => $dwight->uuid])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.1', ['slice' => 'past', 'count' => 1, 'total' => 2])
            ->assertJsonPath('data.2', ['slice' => '2025', 'count' => 1, 'total' => 2])
            ->assertJsonPath('meta.today', '2026-11-22');
    }

    #[Test]
    public function it_counts_the_slices_under_a_type_deleted_since(): void
    {
        $this->travelTo('2026-11-22 10:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $prank = $this->lifeEventWith($vault, '2025-05-01', $jim);
        $this->lifeEventWith($vault, '2025-06-01', $jim);
        $prank->lifeEventType->delete();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-event-slices", ['life_event_type' => $prank->lifeEventType->uuid])
            ->assertOk()
            ->assertJsonPath('data.2', ['slice' => '2025', 'count' => 1, 'total' => 2]);
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_or_a_type_it_cannot_reach(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-event-slices", ['contact' => Contact::factory()->create()->uuid])
            ->assertNotFound();

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/life-event-slices", ['life_event_type' => LifeEventType::factory()->create()->uuid])
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/vaults/'.Vault::factory()->create()->uuid.'/life-event-slices')
            ->assertNotFound();
    }

    private function lifeEventWith(Vault $vault, string $happenedOn, Contact $contact): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => $happenedOn]);

        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $contact->id]);

        return $lifeEvent;
    }
}
