<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Contact\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $this->assertTrue($activityType->account()->exists());
    }

    public function test_it_belongs_to_a_category()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $this->assertTrue($activityType->category()->exists());
    }

    public function test_it_gets_the_name_attribute()
    {
        $activityType = factory(ActivityType::class)->create([
            'translation_key' => 'awesome_key',
            'name' => null,
        ]);

        $this->assertEquals(
            'people.activity_type_awesome_key',
            $activityType->name
        );

        $activityType = factory(ActivityType::class)->create([
            'translation_key' => null,
            'name' => 'awesome_name',
        ]);

        $this->assertEquals(
            'awesome_name',
            $activityType->name
        );
    }
}
