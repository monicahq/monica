<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_note_on_the_group(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();

        $this->actingAs($user)->post(route('groups.notes.create', ['vault' => $vault, 'group' => $group]), ['body' => 'No paper plates.'])
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]))
            ->assertSessionHas('status', 'The note is saved on the group.');

        $this->assertDatabaseHas('group_notes', ['group_id' => $group->id, 'body' => 'No paper plates.']);
    }

    #[Test]
    public function it_refuses_an_empty_note(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        $this->actingAs($user)->post(route('groups.notes.create', ['vault' => $vault, 'group' => $group]), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    #[Test]
    public function it_opens_a_note_for_editing_in_the_history(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'No paper plates.']);

        $this->actingAs($user)->get(route('groups.notes.edit', ['vault' => $vault, 'group' => $group, 'groupNote' => $note]))
            ->assertOk()
            ->assertSee('action="'.route('groups.notes.update', ['vault' => $vault, 'group' => $group, 'groupNote' => $note]).'"', false);
    }

    #[Test]
    public function it_changes_a_note(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->put(route('groups.notes.update', ['vault' => $vault, 'group' => $group, 'groupNote' => $note]), ['body' => 'Paper plates are fine.'])
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->assertEquals('Paper plates are fine.', $note->refresh()->body);
    }

    #[Test]
    public function it_refuses_an_emptied_note_apart_from_the_writing_box(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->put(route('groups.notes.update', ['vault' => $vault, 'group' => $group, 'groupNote' => $note]), ['body' => ''])
            ->assertSessionHasErrors('body', errorBag: 'editNote')
            ->assertSessionDoesntHaveErrors('body');
    }

    #[Test]
    public function it_deletes_a_note(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->delete(route('groups.notes.destroy', ['vault' => $vault, 'group' => $group, 'groupNote' => $note]))
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->assertSoftDeleted($note);
    }

    #[Test]
    public function it_does_not_find_a_note_through_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->delete(route('groups.notes.destroy', ['vault' => $vault, 'group' => $group, 'groupNote' => $note]))
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function finerThingsClub(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club'])];
    }
}
