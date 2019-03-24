<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Activity\Activity\CreateActivity;
use App\Services\Account\Activity\Activity\DestroyActivity;

class DestroyActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_activity()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
        ];

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
        ]);

        app(DestroyActivity::class)->execute($request);

        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);
    }

    public function test_it_removes_the_journal_entry_when_destroying_the_activity()
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

        $activity = app(CreateActivity::class)->execute($request);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
        ];
        app(DestroyActivity::class)->execute($request);

        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);

        $this->assertDatabaseMissing('journal_entries', [
            'account_id' => $account->id,
            'journalable_id' => $activity->id,
            'journalable_type' => get_class($activity),
        ]);
    }
}
