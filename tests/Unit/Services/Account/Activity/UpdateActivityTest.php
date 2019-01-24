<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Activity;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Activity\Activity\UpdateActivity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_an_activity()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        (new UpdateActivity)->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'account_id' => $activity->account_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateActivity)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $activity = factory(Activity::class)->create([]);
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        $this->expectException(ModelNotFoundException::class);
        (new UpdateActivity)->execute($request);
    }
}
