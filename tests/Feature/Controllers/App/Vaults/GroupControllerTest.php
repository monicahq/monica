<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_groups_of_the_vault(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);

        $this->actingAs($user)->get(route('groups.index', $vault))
            ->assertOk()
            ->assertSee('Halpert household');
    }

    #[Test]
    public function it_creates_a_group(): void
    {
        Queue::fake();
        [$user, $vault] = $this->vaultOfSomebody();

        $response = $this->actingAs($user)->post(route('groups.create', $vault), [
            'name' => 'Party planning committee',
            'started_on' => '2005-01-01',
            'started_precision' => 'year',
        ]);

        $group = Group::query()->where('name', 'Party planning committee')->firstOrFail();

        $response->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));
        $response->assertSessionHas('status', 'The group Party planning committee is created.');
    }

    #[Test]
    public function it_refuses_a_group_without_a_name(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();

        $this->actingAs($user)->post(route('groups.create', $vault), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_shows_a_group_with_its_members(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm']);
        $mose = GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Cousin']);

        $this->actingAs($user)->get(route('groups.show', ['vault' => $vault, 'group' => $group]))
            ->assertOk()
            ->assertSee('Schrute farm')
            ->assertSee($mose->contact->name)
            ->assertDontSee('Delete this group');
    }

    #[Test]
    public function it_opens_a_group_by_its_slug_and_by_its_uuid(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm']);

        $this->assertStringEndsWith("/groups/{$group->slug}", route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->actingAs($user)->get("/vaults/{$vault->slug}/groups/schrute-farm")->assertOk();
        $this->actingAs($user)->get("/vaults/{$vault->slug}/groups/{$group->uuid}")->assertOk();
    }

    #[Test]
    public function it_offers_to_delete_a_group_from_its_edit_screen(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm']);

        $this->actingAs($user)->get(route('groups.edit', ['vault' => $vault, 'group' => $group]))
            ->assertOk()
            ->assertSee('Delete this group')
            ->assertSee(route('groups.destroy', ['vault' => $vault, 'group' => $group]), false);
    }

    #[Test]
    public function it_saves_a_group(): void
    {
        Queue::fake();
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Stamford branch']);

        $response = $this->actingAs($user)->put(route('groups.update', ['vault' => $vault, 'group' => $group]), ['name' => 'Scranton branch']);

        $this->assertSame('Scranton branch', $group->refresh()->name);
        $response->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]))
            ->assertSessionHas('status', 'The group is saved.');
    }

    #[Test]
    public function it_deletes_a_group_and_offers_it_back(): void
    {
        Queue::fake();
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);

        $this->actingAs($user)->delete(route('groups.destroy', ['vault' => $vault, 'group' => $group]))
            ->assertRedirect(route('groups.index', $vault));

        $this->assertSoftDeleted($group);

        $this->actingAs($user)->get(route('groups.index', $vault))
            ->assertSee('The group Finer things club is deleted.')
            ->assertSee(route('groups.restoration.update', ['vault' => $vault, 'groupUuid' => $group->uuid]), false);
    }

    #[Test]
    public function it_does_not_find_a_group_through_another_vault(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->actingAs($user)->get(route('groups.show', ['vault' => $vault, 'group' => $group]))->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_while_groups_are_off(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        $this->actingAs($user)->get(route('groups.index', $vault))->assertNotFound();
    }

    /** @return array{0: User, 1: Vault} */
    private function vaultOfSomebody(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
