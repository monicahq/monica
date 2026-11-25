<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreGroupMembership;
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

class RestoreGroupMembershipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_it_back_from_the_trash(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $membership = GroupMember::factory()->create(['group_id' => $group->id, 'deleted_at' => now()]);

        new RestoreGroupMembership(user: $user, vault: $vault, membership: $membership)->execute();

        $this->assertNotSoftDeleted($membership);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupMembershipRestoration);
    }

    #[Test]
    public function it_refuses_a_record_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['account_id' => $user->account_id]);
        $membership = GroupMember::factory()->create(['group_id' => $group->id, 'deleted_at' => now()]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreGroupMembership(user: $user, vault: $vault, membership: $membership)->execute();
    }
}
