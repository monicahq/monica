<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupHistoryBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupHistoryBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_interleaves_activities_and_notes_newest_first(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'happened_on' => '2026-10-31', 'title' => 'Halloween party']);
        GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'Angela keeps the budget.', 'created_at' => '2026-11-02 10:00:00']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'happened_on' => '2026-12-24', 'title' => 'Christmas party']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '2026-12-25', 'title' => 'Not the committee']);

        $entries = new GroupHistoryBlock(user: $user, vault: $vault, group: $group)->entries();

        $this->assertSame(['Christmas party', null, 'Halloween party'], array_column($entries, 'title'));
        $this->assertSame(['activity', 'note', 'activity'], array_column($entries, 'kind'));
        $this->assertSame('Angela keeps the budget.', $entries[1]['body']);
    }

    #[Test]
    public function it_says_who_was_there_out_of_the_people_in_the_group(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $angela = $this->member($group, 'Angela');
        $phyllis = $this->member($group, 'Phyllis');
        $this->member($group, 'Meredith');
        $party = LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'happened_on' => '2026-10-31']);
        LifeEventParticipant::factory()->create(['life_event_id' => $party->id, 'participant_type' => Contact::class, 'participant_id' => $angela->id, 'position' => 0]);
        LifeEventParticipant::factory()->create(['life_event_id' => $party->id, 'participant_type' => Contact::class, 'participant_id' => $phyllis->id, 'position' => 1]);

        $entry = new GroupHistoryBlock(user: $user, vault: $vault, group: $group)->entries()[0];

        $this->assertSame('2 of 3 were there: Angela and Phyllis', $entry['participantLine']);
        $this->assertSame(route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $angela, 'lifeEvent' => $party]), $entry['editUrl']);
    }

    #[Test]
    public function it_offers_more_once_a_page_is_full(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        GroupNote::factory()->count(GroupHistoryBlock::PER_PAGE + 1)->create(['group_id' => $group->id]);

        $first = new GroupHistoryBlock(user: $user, vault: $vault, group: $group);
        $second = new GroupHistoryBlock(user: $user, vault: $vault, group: $group, page: 2);

        $this->assertCount(GroupHistoryBlock::PER_PAGE, $first->entries());
        $this->assertSame(route('groups.show', ['vault' => $vault, 'group' => $group, 'page' => 2]), $first->nextPageUrl());
        $this->assertCount(GroupHistoryBlock::PER_PAGE + 1, $second->entries());
        $this->assertNull($second->nextPageUrl());
    }

    #[Test]
    public function it_holds_nothing_for_a_group_without_history(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();

        $this->assertTrue(new GroupHistoryBlock(user: $user, vault: $vault, group: $group)->holdsNothing());
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function partyPlanningCommittee(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id])];
    }

    private function member(Group $group, string $firstName): Contact
    {
        $contact = Contact::factory()->create(['account_id' => $group->account_id, 'vault_id' => $group->vault_id, 'first_name' => $firstName]);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $contact->id]);

        return $contact;
    }
}
