<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Group;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupLifeEventControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'name',
            'title',
            'life_event_type' => ['id', 'name'],
            'group' => ['id', 'name'],
            'happened_on',
            'narrative',
            'participants',
            'created_from',
            'created_at',
        ],
        'links' => ['self', 'web'],
    ];

    #[Test]
    public function it_lists_the_activities_of_the_group_newest_first(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $older = LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'happened_on' => '2026-03-01']);
        $newer = LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'happened_on' => '2026-05-01']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '2026-06-01']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/life-events");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newer->uuid);
        $response->assertJsonPath('data.1.id', $older->uuid);
        $response->assertJsonPath('data.0.attributes.group.name', 'Party planning committee');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_account(): void
    {
        [$user] = $this->partyPlanningCommittee();
        $group = Group::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/life-events")->assertNotFound();
        $this->json('POST', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/life-events", [])->assertNotFound();
    }

    #[Test]
    public function it_records_an_activity_on_the_group_with_its_participants(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $angela = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Angela']);
        $phyllis = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Phyllis']);
        $meeting = $this->lifeEventTypeAsking($user, 'Meeting');

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/life-events", [
            'life_event_type' => $meeting->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$angela->uuid, $phyllis->uuid],
            'title' => 'Budget review',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.group.id', $group->uuid);
        $response->assertJsonPath('data.attributes.created_from', 'group');
        $response->assertJsonPath('data.attributes.participants.0.name', $angela->name);
        $response->assertJsonPath('data.attributes.participants.1.name', $phyllis->name);
        $this->assertDatabaseHas('life_events', ['vault_id' => $vault->id, 'group_id' => $group->id, 'title' => 'Budget review', 'created_from' => 'group', 'participant_count' => 2]);
    }

    #[Test]
    public function it_requires_participants_and_a_type_and_a_day(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/life-events", ['participants' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['life_event_type', 'happened_on', 'participants']);
    }

    #[Test]
    public function it_refuses_a_type_that_can_no_longer_be_chosen(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $angela = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $funRun = $this->lifeEventTypeAsking($user, 'Fun run');
        $funRun->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/life-events", [
            'life_event_type' => $funRun->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$angela->uuid],
        ])->assertUnprocessable()->assertJsonValidationErrors(['life_event_type']);
    }

    #[Test]
    public function it_returns_not_found_for_a_participant_of_another_vault(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $nashua = Vault::factory()->create(['account_id' => $user->account_id]);
        $stranger = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $nashua->id]);
        $meeting = $this->lifeEventTypeAsking($user, 'Meeting');

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/life-events", [
            'life_event_type' => $meeting->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$stranger->uuid],
        ])->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $otherVault->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/life-events")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function partyPlanningCommittee(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee'])];
    }
}
