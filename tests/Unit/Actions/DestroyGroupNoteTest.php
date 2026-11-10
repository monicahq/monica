<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyGroupNote;
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

class DestroyGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        new DestroyGroupNote(user: $user, vault: $vault, group: $group, note: $note)->execute();

        $this->assertSoftDeleted($note);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupNoteDeletion);
    }

    #[Test]
    public function it_refuses_a_note_about_another_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyGroupNote(user: $user, vault: $vault, group: $group, note: $note)->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = GroupNote::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyGroupNote(user: $user, vault: $vault, group: $note->group, note: $note)->execute();
    }
}
