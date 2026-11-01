<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_form_with_a_labelled_picker(): void
    {
        [$user, $vault, $group] = $this->scrantonBranch();

        $this->actingAs($user)->get(route('groups.members.new', ['vault' => $vault, 'group' => $group]))
            ->assertOk()
            ->assertSee('Add somebody to Scranton branch')
            ->assertSee('for="contact"', false)
            ->assertSee('aria-controls="contact-choices"', false)
            ->assertSee('name="contact"', false);
    }

    #[Test]
    public function it_puts_somebody_in_the_group(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        $this->actingAs($user)->post(route('groups.members.create', ['vault' => $vault, 'group' => $group]), [
            'contact' => $pam->uuid,
            'role' => 'Receptionist',
        ])
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'contact_id' => $pam->id, 'role' => 'Receptionist']);
    }

    #[Test]
    public function it_refuses_somebody_already_in_the_group_on_the_form(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $dwight = GroupMember::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->post(route('groups.members.create', ['vault' => $vault, 'group' => $group]), ['contact' => $dwight->contact->uuid])
            ->assertSessionHasErrors('contact');
    }

    #[Test]
    public function it_records_that_somebody_left(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Temp']);

        $this->actingAs($user)->put(route('groups.members.update', ['vault' => $vault, 'group' => $group, 'membership' => $ryan]), [
            'role' => 'Temp',
            'left_on' => '2007-01-01',
            'left_precision' => 'year',
        ])->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->assertFalse($ryan->refresh()->isCurrent());
    }

    #[Test]
    public function it_deletes_a_membership_recorded_by_mistake(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $membership = GroupMember::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->delete(route('groups.members.destroy', ['vault' => $vault, 'group' => $group, 'membership' => $membership]))
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->assertSoftDeleted($membership);
    }

    #[Test]
    public function it_does_not_find_a_membership_through_another_group(): void
    {
        [$user, $vault, $group] = $this->scrantonBranch();
        $elsewhere = GroupMember::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->get(route('groups.members.edit', ['vault' => $vault, 'group' => $group, 'membership' => $elsewhere]))->assertNotFound();
    }

    #[Test]
    public function it_offers_the_people_of_the_vault_not_already_in_the_group(): void
    {
        [$user, $vault, $group] = $this->scrantonBranch();
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);
        $oscar = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Oscar', 'last_name' => 'Martinez']);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $oscar->id]);

        $this->actingAs($user)->get(route('groups.memberCandidates.index', ['vault' => $vault, 'group' => $group]))
            ->assertOk()
            ->assertSee('Kevin')
            ->assertDontSee('Oscar');
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function scrantonBranch(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch'])];
    }
}
