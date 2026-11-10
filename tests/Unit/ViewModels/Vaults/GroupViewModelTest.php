<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_people_in_the_group_today_before_those_who_left(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'left_on' => '2007-01-01', 'left_precision' => DatePrecisionEnum::Year, 'role' => 'Temp']);
        $jim = GroupMember::factory()->create(['group_id' => $group->id, 'joined_on' => '2001-03-01', 'joined_precision' => DatePrecisionEnum::Month]);

        $members = new GroupViewModel(user: $user, vault: $vault, group: $group)->members()->items();

        $this->assertSame([$jim->contact->name, $ryan->contact->name], array_column($members, 'name'));
        $this->assertTrue($members[0]['isCurrent']);
        $this->assertFalse($members[1]['isCurrent']);
        $this->assertSame('Temp', $members[1]['role']);
        $this->assertSame('Until 2007', $members[1]['period']);
        $this->assertSame('Since March 2001', $members[0]['period']);
    }

    #[Test]
    public function it_leaves_out_a_deleted_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        GroupMember::factory()->create(['group_id' => $group->id])->contact->delete();

        $this->assertCount(0, new GroupViewModel(user: $user, vault: $vault, group: $group)->members()->items());
    }

    #[Test]
    public function it_points_out_a_membership_outside_the_dates_of_the_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'started_on' => '2005-01-01', 'started_precision' => DatePrecisionEnum::Year]);
        GroupMember::factory()->create(['group_id' => $group->id, 'joined_on' => '2001-01-01', 'joined_precision' => DatePrecisionEnum::Year]);
        GroupMember::factory()->create(['group_id' => $group->id, 'joined_on' => '2006-01-01', 'joined_precision' => DatePrecisionEnum::Year]);

        $members = new GroupViewModel(user: $user, vault: $vault, group: $group)->members()->items();

        $this->assertSame([true, false], array_column($members, 'isOutsideTheGroupDates'));
    }

    #[Test]
    public function it_says_how_many_people_belong_to_the_group_today(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $empty = Group::factory()->create(['vault_id' => $vault->id]);
        GroupMember::factory()->count(2)->create(['group_id' => $group->id]);
        GroupMember::factory()->former()->create(['group_id' => $group->id]);

        $viewModel = new GroupViewModel(user: $user, vault: $vault, group: $group);

        $this->assertSame('2 people belong to it', $viewModel->headcountLine());
        $this->assertCount(2, $viewModel->avatarNames());
        $this->assertSame(3, $viewModel->membersCount());
        $this->assertSame('Nobody belongs to it yet', new GroupViewModel(user: $user, vault: $vault, group: $empty)->headcountLine());
    }

    #[Test]
    public function it_counts_how_many_of_the_group_activities_each_member_came_to(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $angela = GroupMember::factory()->create(['group_id' => $group->id]);
        $oscar = GroupMember::factory()->create(['group_id' => $group->id]);
        foreach (['2026-10-31', '2026-12-24'] as $day) {
            $party = LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'happened_on' => $day]);
            LifeEventParticipant::factory()->create(['life_event_id' => $party->id, 'participant_type' => Contact::class, 'participant_id' => $angela->contact_id]);
        }
        $elsewhere = LifeEvent::factory()->create(['vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $elsewhere->id, 'participant_type' => Contact::class, 'participant_id' => $oscar->contact_id]);

        $members = collect(new GroupViewModel(user: $user, vault: $vault, group: $group)->members()->items())->keyBy('name');

        $this->assertSame('At 2 activities', $members[$angela->contact->name]['participation']);
        $this->assertSame('No activities yet', $members[$oscar->contact->name]['participation']);
    }

    #[Test]
    public function it_leaves_the_files_tab_out_when_files_are_not_offered(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        GroupFile::factory()->create(['group_id' => $group->id]);

        $this->assertSame(1, new GroupViewModel(user: $user, vault: $vault, group: $group)->filesCount());

        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->assertNull(new GroupViewModel(user: $user, vault: $vault, group: $group)->filesCount());
    }
}
