<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateGroupMembership;
use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Jobs\LogUserAction;
use App\Models\Contact;
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

class CreateGroupMembershipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_contact_in_a_group(): void
    {
        Queue::fake();
        $this->travelTo('2026-10-29 10:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household', 'updated_at' => '2026-01-01']);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);

        $membership = new CreateGroupMembership(
            user: $user,
            vault: $vault,
            group: $group,
            contact: $jim,
            role: 'Father',
            joinedOn: CarbonImmutable::parse('2009-10-08'),
            joinedPrecision: DatePrecisionEnum::Day,
        )->execute();

        $this->assertInstanceOf(GroupMember::class, $membership);
        $this->assertDatabaseHas('group_members', [
            'id' => $membership->id,
            'account_id' => $user->account_id,
            'group_id' => $group->id,
            'contact_id' => $jim->id,
            'role' => 'Father',
            'joined_precision' => 'day',
            'left_on' => null,
        ]);
        $this->assertEquals('2026-10-29', $group->fresh()->updated_at->format('Y-m-d'));

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupMembershipCreation);
    }

    #[Test]
    public function it_accepts_the_me_contact_of_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $me = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'is_me' => true]);

        new CreateGroupMembership(user: $user, vault: $vault, group: $group, contact: $me)->execute();

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'contact_id' => $me->id]);
    }

    #[Test]
    public function it_refuses_somebody_already_in_the_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $dwight->id]);

        $this->expectException(ContactIsAlreadyInTheGroup::class);

        new CreateGroupMembership(user: $user, vault: $vault, group: $group, contact: $dwight)->execute();
    }

    #[Test]
    public function it_starts_a_second_membership_for_somebody_who_came_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch']);
        $ryan = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $first = GroupMember::factory()->former()->create(['group_id' => $group->id, 'contact_id' => $ryan->id, 'role' => 'Temp']);

        $second = new CreateGroupMembership(
            user: $user,
            vault: $vault,
            group: $group,
            contact: $ryan,
            joinedOn: CarbonImmutable::parse('2009-01-01'),
            joinedPrecision: DatePrecisionEnum::Year,
        )->execute();

        $this->assertNotEquals($first->id, $second->id);
        $this->assertEquals('2007-01-01', $first->fresh()->left_on->format('Y-m-d'));
        $this->assertEquals('Temp', $first->fresh()->role);
        $this->assertTrue($second->isCurrent());
    }

    #[Test]
    public function it_refuses_a_departure_before_the_arrival(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $ryan = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        $this->expectException(MembershipEndsBeforeItStarts::class);

        new CreateGroupMembership(
            user: $user,
            vault: $vault,
            group: $group,
            contact: $ryan,
            joinedOn: CarbonImmutable::parse('2009-01-01'),
            leftOn: CarbonImmutable::parse('2007-01-01'),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $karen = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        new CreateGroupMembership(user: $user, vault: $vault, group: $group, contact: $karen)->execute();
    }
}
