<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyGroupMembership;
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

class DestroyGroupMembershipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_membership_recorded_by_mistake(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $membership = GroupMember::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        new DestroyGroupMembership(user: $user, vault: $vault, membership: $membership)->execute();

        $this->assertSoftDeleted($membership);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupMembershipDeletion);
    }

    #[Test]
    public function it_refuses_a_membership_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyGroupMembership(user: $user, vault: $vault, membership: GroupMember::factory()->create())->execute();
    }
}
