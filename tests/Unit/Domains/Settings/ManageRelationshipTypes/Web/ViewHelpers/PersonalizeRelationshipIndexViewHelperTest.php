<?php

namespace Tests\Unit\Domains\Settings\ManageRelationshipTypes\Web\ViewHelpers;

use App\Domains\Settings\ManageRelationshipTypes\Web\ViewHelpers\PersonalizeRelationshipIndexViewHelper;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeRelationshipIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $group = RelationshipGroupType::factory()->create();
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);

        $array = PersonalizeRelationshipIndexViewHelper::data($group->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('group_types', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'group_type_store' => env('APP_URL').'/settings/personalize/relationships',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_relationship_group_type(): void
    {
        $group = RelationshipGroupType::factory()->create();

        $array = PersonalizeRelationshipIndexViewHelper::dtoGroupType($group);
        $this->assertEquals(
            5,
            count($array)
        );
        $this->assertArrayHasKey('types', $array);
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/relationships/'.$group->id.'/types',
                'update' => env('APP_URL').'/settings/personalize/relationships/'.$group->id,
                'destroy' => env('APP_URL').'/settings/personalize/relationships/'.$group->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_relationship_type(): void
    {
        $group = RelationshipGroupType::factory()->create();
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);

        $array = PersonalizeRelationshipIndexViewHelper::dtoRelationshipType($group, $type);
        $this->assertEquals(
            5,
            count($array)
        );
        $this->assertEquals(
            [
                'id' => $type->id,
                'name' => $type->name,
                'name_reverse_relationship' => $type->name_reverse_relationship,
                'can_be_deleted' => null,
                'url' => [
                    'update' => env('APP_URL').'/settings/personalize/relationships/'.$group->id.'/types/'.$type->id,
                    'destroy' => env('APP_URL').'/settings/personalize/relationships/'.$group->id.'/types/'.$type->id,
                ],
            ],
            $array
        );
    }
}
