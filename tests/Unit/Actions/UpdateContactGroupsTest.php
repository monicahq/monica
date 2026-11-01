<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactGroups;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactGroupsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_somebody_to_the_groups_ticked_and_takes_them_out_of_the_others_today(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2026-10-31');
        [$user, $vault, $jim] = $this->jim();
        $finerThings = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $salesTeam = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Sales team']);
        $stamford = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Stamford branch']);
        GroupMember::factory()->create(['group_id' => $salesTeam->id, 'contact_id' => $jim->id, 'joined_on' => '2005-03-24']);
        GroupMember::factory()->create(['group_id' => $stamford->id, 'contact_id' => $jim->id, 'joined_on' => '2007-01-01']);

        new UpdateContactGroups(user: $user, vault: $vault, contact: $jim, groups: new Collection([$finerThings, $salesTeam]))->execute();

        $this->assertDatabaseHas('group_members', ['group_id' => $finerThings->id, 'contact_id' => $jim->id, 'left_on' => null]);
        $this->assertSame('2026-10-31', GroupMember::query()->where('group_id', $finerThings->id)->sole()->joined_on->format('Y-m-d'));
        $this->assertNull(GroupMember::query()->where('group_id', $salesTeam->id)->sole()->left_on);
        $this->assertSame('2026-10-31', GroupMember::query()->where('group_id', $stamford->id)->sole()->left_on->format('Y-m-d'));
    }

    #[Test]
    public function it_leaves_a_membership_that_already_ended_as_it_is(): void
    {
        Queue::fake();
        [$user, $vault, $jim] = $this->jim();
        $stamford = Group::factory()->create(['vault_id' => $vault->id]);
        GroupMember::factory()->create(['group_id' => $stamford->id, 'contact_id' => $jim->id, 'left_on' => '2007-12-01']);

        new UpdateContactGroups(user: $user, vault: $vault, contact: $jim, groups: new Collection)->execute();

        $this->assertSame('2007-12-01', GroupMember::query()->where('group_id', $stamford->id)->sole()->left_on->format('Y-m-d'));
    }

    #[Test]
    public function it_refuses_a_group_of_another_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        [$user, $vault, $jim] = $this->jim();
        $elsewhere = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        new UpdateContactGroups(user: $user, vault: $vault, contact: $jim, groups: new Collection([$elsewhere]))->execute();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function jim(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim'])];
    }
}
