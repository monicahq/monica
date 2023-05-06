<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupEditViewHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GroupEditViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $groupType = GroupType::factory()->create([
            'account_id' => $contact->vault->account_id,
        ]);
        $group = Group::factory()->create([
            'vault_id' => $contact->vault_id,
            'group_type_id' => $groupType->id,
        ]);
        $array = GroupEditViewHelper::data($group);

        $this->assertCount(
            5,
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
            $groupType->id,
            $array['group_type_id']
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $groupType->id,
                    'name' => $groupType->label,
                ],
            ],
            $array['group_types']->toArray()
        );

        $this->assertEquals(
            [
                'back' => env('APP_URL').'/vaults/'.$contact->vault->id.'/groups/'.$group->id,
                'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/groups/'.$group->id,
            ],
            $array['url']
        );
    }
}
