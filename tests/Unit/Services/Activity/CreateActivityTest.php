<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use Illuminate\Validation\ValidationException;
use App\Services\Activity\Activity\CreateActivity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_activity()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        $activityService = new CreateActivity;
        $activity = $activityService->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'account_id' => $account->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);
        (new CreateActivity)->execute($request);
    }

    public function test_it_throws_an_exception_if_activity_type_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        $this->expectException(ModelNotFoundException::class);
        (new CreateActivity)->execute($request);
    }
}
