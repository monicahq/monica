<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyGroup;
use App\Actions\RestoreGroup;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_the_group_back_with_its_memberships(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee']);
        GroupMember::factory()->count(2)->create(['group_id' => $group->id]);
        $group->delete();

        new RestoreGroup(user: $user, vault: $vault, group: $group)->execute();

        $this->assertNotSoftDeleted($group);
        $this->assertCount(2, $group->fresh()->currentMembers);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupRestoration);
    }

    #[Test]
    public function it_brings_back_what_went_to_the_trash_with_the_group_and_leaves_what_was_deleted_before(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer Things Club']);
        $pam = GroupMember::factory()->create(['group_id' => $group->id]);
        $note = GroupNote::factory()->create(['group_id' => $group->id]);
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);
        $oscar = GroupMember::factory()->create(['group_id' => $group->id, 'deleted_at' => now()->subDay()]);
        new DestroyGroup(user: $user, vault: $vault, group: $group)->execute();

        new RestoreGroup(user: $user, vault: $vault, group: $group->fresh())->execute();

        $this->assertNotSoftDeleted('group_members', ['id' => $pam->id, 'deleted_with_type' => null]);
        $this->assertNotSoftDeleted($note);
        $this->assertNotSoftDeleted($attachment);
        $this->assertSoftDeleted($oscar);
    }

    #[Test]
    public function it_brings_back_the_reminders_its_deletion_stopped(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $reminder = Reminder::factory()->manual($group, '2027-12-01')->inactive(ReminderInactiveReasonEnum::RecordDeleted)->create();
        $group->delete();

        new RestoreGroup(user: $user, vault: $vault, group: $group)->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => true, 'inactive_reason' => null]);
    }

    #[Test]
    public function it_refuses_a_group_that_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreGroup(user: $user, vault: $vault, group: $group)->execute();
    }
}
