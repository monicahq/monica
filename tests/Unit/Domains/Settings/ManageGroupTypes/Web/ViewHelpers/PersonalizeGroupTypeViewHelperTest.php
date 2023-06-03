<?php

namespace Tests\Unit\Domains\Settings\ManageGroupTypes\Web\ViewHelpers;

use App\Domains\Settings\ManageGroupTypes\Web\ViewHelpers\PersonalizeGroupTypeViewHelper;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeGroupTypeViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $groupType = GroupType::factory()->create();
        $array = PersonalizeGroupTypeViewHelper::data($groupType->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('group_types', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'store' => env('APP_URL').'/settings/personalize/groupTypes',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $groupType = GroupType::factory()->create();
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
            'label' => 'Father',
        ]);
        $array = PersonalizeGroupTypeViewHelper::dto($groupType);

        $this->assertEquals(
            $groupType->id,
            $array['id']
        );
        $this->assertEquals(
            $groupType->label,
            $array['label']
        );
        $this->assertEquals(
            $groupType->position,
            $array['position']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $groupTypeRole->id,
                    'label' => 'Father',
                    'position' => 1,
                    'group_type_id' => $groupType->id,
                    'url' => [
                        'position' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id.'/groupTypeRoles/'.$groupTypeRole->id.'/position',
                        'update' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id.'/groupTypeRoles/'.$groupTypeRole->id,
                        'destroy' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id.'/groupTypeRoles/'.$groupTypeRole->id,
                    ],
                ],
            ],
            $array['group_type_roles']->toArray()
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id.'/groupTypeRoles',
                'position' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id.'/position',
                'update' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id,
                'destroy' => env('APP_URL').'/settings/personalize/groupTypes/'.$groupType->id,
            ],
            $array['url']
        );
    }
}
