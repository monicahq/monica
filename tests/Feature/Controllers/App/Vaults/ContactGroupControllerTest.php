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

class ContactGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_of_the_vault_to_tick(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $salesTeam = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Sales team']);
        Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        GroupMember::factory()->create(['group_id' => $salesTeam->id, 'contact_id' => $jim->id]);

        $this->actingAs($user)->get(route('contacts.groups.edit', ['vault' => $vault, 'contact' => $jim]))
            ->assertOk()
            ->assertSee('Sales team')
            ->assertSee('Finer things club')
            ->assertSee('name="groups[]"', false)
            ->assertSee(route('contacts.groups.new', ['vault' => $vault, 'contact' => $jim]), false);
    }

    #[Test]
    public function it_saves_the_groups_ticked(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->jim();
        $salesTeam = Group::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->put(route('contacts.groups.update', ['vault' => $vault, 'contact' => $jim]), ['groups' => [$salesTeam->uuid, 'not-a-uuid']])
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('group_members', ['group_id' => $salesTeam->id, 'contact_id' => $jim->id, 'left_on' => null]);
    }

    #[Test]
    public function it_creates_a_group_with_the_contact_in_it_and_opens_it(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->jim();

        $this->actingAs($user)->get(route('contacts.groups.new', ['vault' => $vault, 'contact' => $jim]))
            ->assertOk()
            ->assertSee(route('contacts.groups.create', ['vault' => $vault, 'contact' => $jim]), false);

        $response = $this->actingAs($user)->post(route('contacts.groups.create', ['vault' => $vault, 'contact' => $jim]), ['name' => 'Athlead']);

        $group = Group::query()->where('vault_id', $vault->id)->sole();
        $response->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));
        $this->assertSame('Athlead', $group->name);
        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'contact_id' => $jim->id, 'left_on' => null]);
    }

    #[Test]
    public function it_refuses_a_group_without_a_name(): void
    {
        [$user, $vault, $jim] = $this->jim();

        $this->actingAs($user)->post(route('contacts.groups.create', ['vault' => $vault, 'contact' => $jim]), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('groups', 0);
    }

    #[Test]
    public function it_does_not_find_a_contact_through_another_vault(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $other = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('contacts.groups.edit', ['vault' => $other, 'contact' => $jim]))->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function jim(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert'])];
    }
}
