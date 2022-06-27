<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Contact\ManageGroups\Web\ViewHelpers\ModuleGroupsViewHelper;
use App\Models\Avatar;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleGroupsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_all_the_groups_for_the_contact(): void
    {
        $contact = Contact::factory()->create();
        $group = Group::factory()->create([
            'vault_id' => $contact->vault_id,
            'name' => 'group',
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $contact->avatar_id = $avatar->id;
        $contact->save();
        $group->contacts()->sync([$contact->id]);

        $array = ModuleGroupsViewHelper::data($contact);

        $this->assertEquals(
            4,
            count($array)
        );
        $this->assertArrayHasKey('groups', $array);
        $this->assertArrayHasKey('available_groups', $array);
        $this->assertArrayHasKey('group_types', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id.'/groups',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_details_of_a_given_group(): void
    {
        $groupType = GroupType::factory()->create();
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
            'label' => 'cto',
        ]);
        $contact = Contact::factory()->create();
        $avatar = Avatar::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $contact->avatar_id = $avatar->id;
        $contact->save();
        $group = Group::factory()->create([
            'vault_id' => $contact->vault_id,
            'group_type_id' => $groupType->id,
            'name' => 'group',
        ]);
        $group->contacts()->sync([$contact->id]);

        $array = ModuleGroupsViewHelper::dto($contact, $group);

        $this->assertEquals(
            7,
            count($array)
        );
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('contacts', $array);
        $this->assertArrayHasKey('roles', $array);
        $this->assertArrayHasKey('selected', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            $group->id,
            $array['id']
        );
        $this->assertEquals(
            'group',
            $array['name']
        );
        $this->assertEquals(
            [
                'id' => $groupType->id,
            ],
            $array['type']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => '123',
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id,
                    ],
                ],
            ],
            $array['contacts']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $groupTypeRole->id,
                    'label' => $groupTypeRole->label,
                ],
            ],
            $array['roles']->toArray()
        );
        $this->assertEquals(
            [
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id.'/groups/'.$group->id,
            ],
            $array['url']
        );
    }
}
