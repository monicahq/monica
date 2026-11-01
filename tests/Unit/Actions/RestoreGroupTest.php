<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreGroup;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupMember;
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
    public function it_refuses_a_group_that_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreGroup(user: $user, vault: $vault, group: $group)->execute();
    }
}
