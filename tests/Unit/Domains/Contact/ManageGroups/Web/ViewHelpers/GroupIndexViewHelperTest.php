<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupIndexViewHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GroupIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_all_the_groups_associated_with_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $groupType = GroupType::factory()->create();
        $parentRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $group = Group::factory()->create([
            'vault_id' => $contact->vault_id,
            'group_type_id' => $groupType->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => $parentRole->id],
        ]);

        $collection = GroupIndexViewHelper::data($vault, $user);

        $this->assertCount(
            1,
            $collection
        );

        $this->assertEquals(
            $group->id,
            $collection->toArray()[0]['id']
        );

        $this->assertEquals(
            $group->name,
            $collection->toArray()[0]['name']
        );

        $this->assertEquals(
            env('APP_URL').'/vaults/'.$vault->id.'/groups/'.$group->id,
            $collection->toArray()[0]['url']['show']
        );
    }
}
