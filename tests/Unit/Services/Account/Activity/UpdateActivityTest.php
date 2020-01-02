<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Activity;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\Activity\UpdateActivity;

class UpdateActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_an_activity()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ];

        app(UpdateActivity::class)->execute($request);

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

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateActivity::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $activity = factory(Activity::class)->create([]);
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateActivity::class)->execute($request);
    }
}
