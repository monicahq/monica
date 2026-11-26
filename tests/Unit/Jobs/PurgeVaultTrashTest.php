<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\PurgeVaultTrash;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeVaultTrashTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_for_good_what_waited_longer_than_the_retention_period(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $membership = GroupMember::factory()->create(['group_id' => $group->id, 'deleted_at' => now()->subDays(31), 'deleted_with_type' => Group::class, 'deleted_with_id' => $group->id]);
        Group::query()->whereKey($group->id)->update(['deleted_at' => now()->subDays(31)]);
        $recent = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(29)]);

        new PurgeVaultTrash()->handle();

        $this->assertModelMissing($group);
        $this->assertModelMissing($membership);
        $this->assertSoftDeleted($recent);
    }

    #[Test]
    public function it_keeps_a_person_absorbed_by_a_merge_whatever_their_age(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $absorbed = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()->subDays(90)]);
        ContactMerge::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'merged_contact_id' => $absorbed->id]);

        new PurgeVaultTrash()->handle();

        $this->assertSoftDeleted($absorbed);
    }

    #[Test]
    public function it_keeps_a_kind_withdrawn_from_an_account_and_purges_the_other_accounts(): void
    {
        $dwight = User::factory()->create();
        $schruteFarms = Vault::factory()->create(['account_id' => $dwight->account_id]);
        $group = Group::factory()->create(['account_id' => $dwight->account_id, 'vault_id' => $schruteFarms->id, 'deleted_at' => now()->subDays(40)]);
        $jim = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $jim->account_id]);
        $roy = Contact::factory()->create(['account_id' => $jim->account_id, 'vault_id' => $scranton->id, 'deleted_at' => now()->subDays(40)]);
        Feature::for($dwight->account)->deactivate('groups');
        Feature::flushCache();

        new PurgeVaultTrash()->handle();

        $this->assertSoftDeleted($group);
        $this->assertModelMissing($roy);
    }
}
