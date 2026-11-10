<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateGroupNote;
use App\Enums\UserActionEnum;
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

class UpdateGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_the_note_says_and_keeps_its_day(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'Party on Friday', 'created_at' => '2026-10-01 10:00:00']);

        new UpdateGroupNote(user: $user, vault: $vault, group: $group, note: $note, body: 'Party on Thursday')->execute();

        $this->assertDatabaseHas('group_notes', ['id' => $note->id, 'body' => 'Party on Thursday']);
        $this->assertEquals('2026-10-01', $note->refresh()->created_at->toDateString());

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupNoteUpdate);
    }

    #[Test]
    public function it_refuses_a_note_about_another_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateGroupNote(user: $user, vault: $vault, group: $group, note: $note, body: 'Changed')->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = GroupNote::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateGroupNote(user: $user, vault: $vault, group: $note->group, note: $note, body: 'Changed')->execute();
    }
}
