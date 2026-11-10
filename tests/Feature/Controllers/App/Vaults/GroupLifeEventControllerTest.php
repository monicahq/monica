<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupLifeEventControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_activity_on_the_group_with_who_was_there(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $angela = $this->memberNamed($group, 'Angela');
        $phyllis = $this->memberNamed($group, 'Phyllis');

        $response = $this->actingAs($user)->post(route('groups.lifeEvents.create', ['vault' => $vault, 'group' => $group]), [
            'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Party')->id,
            'happened_on' => '2026-10-31',
            'title' => 'Halloween party',
            'participants' => [$angela->id, $phyllis->id],
        ]);

        $lifeEvent = LifeEvent::query()->where('group_id', $group->id)->firstOrFail();

        $response->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));
        $response->assertSessionHas('lifeEventJustAdded', $lifeEvent->id);
        $this->assertEquals(2, $lifeEvent->participant_count);
    }

    #[Test]
    public function it_refuses_an_activity_nobody_was_at(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();

        $this->actingAs($user)->post(route('groups.lifeEvents.create', ['vault' => $vault, 'group' => $group]), [
            'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Party')->id,
            'happened_on' => '2026-10-31',
            'participants' => [],
        ])->assertSessionHasErrors('participants');
    }

    #[Test]
    public function it_does_not_find_an_participant_kept_in_another_vault(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $stranger = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->actingAs($user)->post(route('groups.lifeEvents.create', ['vault' => $vault, 'group' => $group]), [
            'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Party')->id,
            'happened_on' => '2026-10-31',
            'participants' => [$stranger->id],
        ])->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function partyPlanningCommittee(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee'])];
    }

    private function memberNamed(Group $group, string $firstName): Contact
    {
        $contact = Contact::factory()->create(['account_id' => $group->account_id, 'vault_id' => $group->vault_id, 'first_name' => $firstName]);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $contact->id]);

        return $contact;
    }
}
