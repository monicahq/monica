<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyGroup;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_group_and_leaves_its_members_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee']);
        $angela = GroupMember::factory()->create(['group_id' => $group->id]);

        new DestroyGroup(user: $user, vault: $vault, group: $group)->execute();

        $this->assertSoftDeleted($group);
        $this->assertNotSoftDeleted($angela);
        $this->assertNotSoftDeleted($angela->contact);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupDeletion);
    }

    #[Test]
    public function it_stops_the_reminders_about_the_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $reminder = Reminder::factory()->manual($group, '2027-12-01')->create();

        new DestroyGroup(user: $user, vault: $vault, group: $group)->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordDeleted->value,
        ]);
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyGroup(user: $user, vault: $vault, group: Group::factory()->create())->execute();
    }
}
