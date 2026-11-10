<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateGroupNote;
use App\Enums\UserActionEnum;
use App\Exceptions\NoteIsEmpty;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_note_on_the_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee']);

        $note = new CreateGroupNote(
            user: $user,
            vault: $vault,
            group: $group,
            body: '  Angela keeps the <b>budget</b> in the binder.  ',
        )->execute();

        $this->assertInstanceOf(GroupNote::class, $note);
        $this->assertDatabaseHas('group_notes', [
            'id' => $note->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'group_id' => $group->id,
            'body' => 'Angela keeps the budget in the binder.',
            'created_by_user_id' => $user->id,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupNoteCreation);
    }

    #[Test]
    public function it_refuses_a_note_with_nothing_written(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(NoteIsEmpty::class);

        new CreateGroupNote(user: $user, vault: $vault, group: $group, body: '<b></b>')->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateGroupNote(user: $user, vault: $vault, group: $group, body: 'Toby is not invited.')->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateGroupNote(user: $user, vault: $vault, group: Group::factory()->create(), body: 'Toby is not invited.')->execute();
    }
}
