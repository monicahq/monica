<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\ActivityType;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTypeCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $this->assertTrue($activityTypeCategory->account()->exists());
    }

    /** @test */
    public function it_has_many_activity_types()
    {
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);
        $activityType = factory(ActivityType::class, 10)->create([
            'activity_type_category_id' => $activityTypeCategory->id,
        ]);

        $this->assertTrue($activityTypeCategory->activityTypes()->exists());
    }

    /** @test */
    public function it_gets_the_name_attribute()
    {
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'translation_key' => 'awesome_key',
            'name' => null,
        ]);

        $this->assertEquals(
            'people.activity_type_category_awesome_key',
            $activityTypeCategory->name
        );

        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'translation_key' => null,
            'name' => 'awesome_name',
        ]);

        $this->assertEquals(
            'awesome_name',
            $activityTypeCategory->name
        );
    }
}
