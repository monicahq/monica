<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateGroupMembership;
use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateGroupMembershipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_that_somebody_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch']);
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Temp']);

        new UpdateGroupMembership(
            user: $user,
            vault: $vault,
            membership: $ryan,
            role: 'Temp',
            joinedOn: null,
            joinedPrecision: DatePrecisionEnum::Unknown,
            leftOn: CarbonImmutable::parse('2007-01-01'),
            leftPrecision: DatePrecisionEnum::Year,
        )->execute();

        $this->assertDatabaseHas('group_members', ['id' => $ryan->id, 'left_precision' => 'year']);
        $this->assertEquals('2007-01-01', $ryan->fresh()->left_on->format('Y-m-d'));

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupMembershipEnd);
    }

    #[Test]
    public function it_changes_a_role_without_ending_the_membership(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Temp']);

        new UpdateGroupMembership(
            user: $user,
            vault: $vault,
            membership: $ryan,
            role: 'Vice president',
            joinedOn: null,
            joinedPrecision: DatePrecisionEnum::Unknown,
            leftOn: null,
            leftPrecision: DatePrecisionEnum::Unknown,
        )->execute();

        $this->assertDatabaseHas('group_members', ['id' => $ryan->id, 'role' => 'Vice president', 'left_on' => null]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupMembershipUpdate);
    }

    #[Test]
    public function it_refuses_a_departure_before_the_arrival(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $ryan = GroupMember::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->expectException(MembershipEndsBeforeItStarts::class);

        new UpdateGroupMembership(
            user: $user,
            vault: $vault,
            membership: $ryan,
            role: null,
            joinedOn: CarbonImmutable::parse('2009-01-01'),
            joinedPrecision: DatePrecisionEnum::Year,
            leftOn: CarbonImmutable::parse('2007-01-01'),
            leftPrecision: DatePrecisionEnum::Year,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_reopen_a_period_while_another_one_is_current(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $current = GroupMember::factory()->create(['group_id' => $group->id]);
        $former = GroupMember::factory()->former()->create(['group_id' => $group->id, 'contact_id' => $current->contact_id]);

        $this->expectException(ContactIsAlreadyInTheGroup::class);

        new UpdateGroupMembership(
            user: $user,
            vault: $vault,
            membership: $former,
            role: null,
            joinedOn: null,
            joinedPrecision: DatePrecisionEnum::Unknown,
            leftOn: null,
            leftPrecision: DatePrecisionEnum::Unknown,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_membership_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $membership = GroupMember::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $elsewhere->id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateGroupMembership(
            user: $user,
            vault: $vault,
            membership: $membership,
            role: null,
            joinedOn: null,
            joinedPrecision: DatePrecisionEnum::Unknown,
            leftOn: null,
            leftPrecision: DatePrecisionEnum::Unknown,
        )->execute();
    }
}
