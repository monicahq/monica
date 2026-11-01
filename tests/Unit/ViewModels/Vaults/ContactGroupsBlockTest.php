<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactGroupsBlock;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactGroupsBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_of_the_contact_current_first_with_their_role(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        GroupMember::factory()->former()->create(['contact_id' => $dwight->id, 'group_id' => Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee'])->id]);
        GroupMember::factory()->create(['contact_id' => $dwight->id, 'role' => 'Owner', 'group_id' => Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm'])->id]);
        $deleted = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        GroupMember::factory()->create(['contact_id' => $dwight->id, 'group_id' => $deleted->id]);
        $deleted->delete();

        $memberships = new ContactGroupsBlock(user: $user, vault: $vault, contact: $dwight)->memberships();

        $this->assertSame(['Schrute farm', 'Party planning committee'], array_column($memberships, 'name'));
        $this->assertSame('Owner', $memberships[0]['role']);
        $this->assertSame([true, false], array_column($memberships, 'isCurrent'));
    }

    #[Test]
    public function it_says_so_when_the_contact_is_in_no_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Toby', 'last_name' => 'Flenderson']);

        $block = new ContactGroupsBlock(user: $user, vault: $vault, contact: $toby);

        $this->assertSame([], $block->memberships());
        $this->assertSame("{$toby->name} is in no group yet.", $block->emptyLine());
    }

    #[Test]
    public function it_is_absent_from_the_contact_page_while_groups_are_off(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertInstanceOf(ContactGroupsBlock::class, new ContactViewModel(user: $user, vault: $vault, contact: $contact)->groups());

        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        $this->assertNull(new ContactViewModel(user: $user, vault: $vault, contact: $contact)->groups());
    }
}
