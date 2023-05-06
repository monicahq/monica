<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupShowViewHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GroupShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_all_the_groups_associated_with_the_contact(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $otherContact = Contact::factory()->create();
        $groupType = GroupType::factory()->create();
        $parentRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $sisterRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $group = Group::factory()->create([
            'vault_id' => $contact->vault_id,
            'group_type_id' => $groupType->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => $parentRole->id],
        ]);
        $group->contacts()->syncWithoutDetaching([
            $otherContact->id => ['group_type_role_id' => $sisterRole->id],
        ]);

        $array = GroupShowViewHelper::data($group);

        $this->assertCount(
            6,
            $array
        );

        $this->assertEquals(
            $group->id,
            $array['id']
        );

        $this->assertEquals(
            $group->name,
            $array['name']
        );

        $this->assertEquals(
            2,
            $array['contact_count']
        );

        $this->assertEquals(
            [
                'edit' => env('APP_URL').'/vaults/'.$contact->vault->id.'/groups/'.$group->id.'/edit',
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/groups/'.$group->id,
            ],
            $array['url']
        );
    }
}
