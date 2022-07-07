<?php

namespace Tests\Unit\Domains\Settings\ManageActivityTypes\Web\ViewHelpers;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Settings\ManageActivityTypes\Web\ViewHelpers\PersonalizeActivityTypesIndexViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function env;

class PersonalizeActivityTypesIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $type = ActivityType::factory()->create();
        $activity = Activity::factory()->create([
            'activity_type_id' => $type->id,
        ]);

        $array = PersonalizeActivityTypesIndexViewHelper::data($type->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('activity_types', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'activity_type_store' => env('APP_URL').'/settings/personalize/activityTypes',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_activity_type(): void
    {
        $type = ActivityType::factory()->create();

        $array = PersonalizeActivityTypesIndexViewHelper::dtoActivityType($type);
        $this->assertEquals(
            4,
            count($array)
        );
        $this->assertArrayHasKey('activities', $array);
        $this->assertEquals(
            $type->label,
            $array['label']
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/activityTypes/'.$type->id.'/activities',
                'update' => env('APP_URL').'/settings/personalize/activityTypes/'.$type->id,
                'destroy' => env('APP_URL').'/settings/personalize/activityTypes/'.$type->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_the_activity(): void
    {
        $type = ActivityType::factory()->create();
        $activity = Activity::factory()->create([
            'activity_type_id' => $type->id,
        ]);

        $array = PersonalizeActivityTypesIndexViewHelper::dtoActivity($type, $activity);
        $this->assertEquals(
            3,
            count($array)
        );
        $this->assertEquals(
            [
                'id' => $activity->id,
                'label' => $activity->label,
                'url' => [
                    'update' => env('APP_URL').'/settings/personalize/activityTypes/'.$type->id.'/activities/'.$activity->id,
                    'destroy' => env('APP_URL').'/settings/personalize/activityTypes/'.$type->id.'/activities/'.$activity->id,
                ],
            ],
            $array
        );
    }
}
