<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $this->assertTrue($activityType->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_category()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $this->assertTrue($activityType->category()->exists());
    }

    /** @test */
    public function it_has_many_activities()
    {
        $account = factory(Account::class)->create();
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);
        $activity = factory(Activity::class, 2)->create([
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertTrue($account->activities()->exists());
    }

    /** @test */
    public function it_gets_the_name_attribute()
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

    /** @test */
    public function it_resets_the_associated_activities()
    {
        $activityType = factory(ActivityType::class)->create([]);
        $activity = factory(Activity::class, 10)->create([
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertEquals(
            10,
            $activityType->activities()->count()
        );

        $this->assertDatabaseHas('activities', [
            'activity_type_id' => $activityType->id,
        ]);

        $activityType->resetAssociationWithActivities();

        $this->assertDatabaseMissing('activities', [
            'activity_type_id' => $activityType->id,
        ]);

        $this->assertEquals(
            0,
            $activityType->activities()->count()
        );
    }
}
