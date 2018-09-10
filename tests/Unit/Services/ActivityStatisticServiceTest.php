<?php

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use App\Models\Contact\ActivityStatistic;
use App\Services\ActivityStatisticService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityStatisticServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_a_list_of_activities_since_a_given_number_of_months()
    {
        $service = new ActivityStatisticService;
        $contact = factory(Contact::class)->create();

        for ($i = 0; $i <= 2; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => Carbon::now()->subMonth(),
            ]);
            $contact->activities()->attach($activity);
        }

        $this->assertCount(
            3,
            $service->activitiesWithContactInTimeRange($contact, Carbon::now()->subMonths(2), Carbon::now())
        );

        $this->assertInstanceOf(
            Activity::class,
            $service->activitiesWithContactInTimeRange($contact, Carbon::now()->subMonths(2), Carbon::now())[1]
        );
    }

    public function test_it_gets_an_empty_list_of_activities()
    {
        $service = new ActivityStatisticService;
        $contact = factory(Contact::class)->create();

        for ($i = 0; $i <= 2; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => Carbon::now()->subYears(2),
            ]);
            $contact->activities()->attach($activity);
        }

        $this->assertCount(
            0,
            $service->activitiesWithContactInTimeRange($contact, Carbon::now()->subMonths(2), Carbon::now())
        );
    }

    public function test_it_gets_a_list_of_unique_activity_types()
    {
        $service = new ActivityStatisticService;
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        // creation of 3 activities with a given activity type
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        for ($i = 0; $i <= 2; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => Carbon::now(),
                'account_id' => $account->id,
                'activity_type_id' => $activityType,
            ]);
            $contact->activities()->attach($activity);
        }

        // creation of 1 activity with a given activity type
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $activity = factory(Activity::class)->create([
            'date_it_happened' => Carbon::now(),
            'account_id' => $account->id,
            'activity_type_id' => $activityType,
        ]);
        $contact->activities()->attach($activity);

        // here we should have 2 uniques activity types, one with 3 and the other with 1 occurence
        $response = $service->uniqueActivityTypesInTimeRange($contact, Carbon::now()->subMonths(2), Carbon::now());

        $this->assertCount(
            2,
            $response
        );

        $this->assertInstanceOf(
            ActivityType::class,
            $response[0]['object']
        );

        $this->assertEquals(
            3,
            $response[0]['occurences']
        );

        $this->assertInstanceOf(
            ActivityType::class,
            $response[1]['object']
        );

        $this->assertEquals(
            1,
            $response[1]['occurences']
        );
    }

    public function test_it_gets_the_breakdown_of_activities_per_year()
    {
        $service = new ActivityStatisticService;
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        for ($i = 0; $i <= 2; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => Carbon::now()->subYears(2),
                'account_id' => $account->id,
            ]);
            $contact->activities()->attach($activity);
        }

        for ($i = 0; $i <= 5; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => Carbon::now(),
                'account_id' => $account->id,
            ]);
            $contact->activities()->attach($activity);
        }

        $activityStatistic = $contact->activityStatistics()->make();
        $activityStatistic->account_id = $contact->account_id;
        $activityStatistic->contact_id = $contact->id;
        $activityStatistic->year = Carbon::now()->year;
        $activityStatistic->count = 6;
        $activityStatistic->save();

        $activityStatistic = $contact->activityStatistics()->make();
        $activityStatistic->account_id = $contact->account_id;
        $activityStatistic->contact_id = $contact->id;
        $activityStatistic->year = Carbon::now()->subYears(2)->year;
        $activityStatistic->count = 3;
        $activityStatistic->save();

        $response = $service->activitiesPerYearWithContact($contact);

        $this->assertCount(
            2,
            $response
        );

        $this->assertEquals(
            6,
            $response[0]->count
        );

        $this->assertEquals(
            3,
            $response[1]->count
        );

        $this->assertInstanceOf(
            ActivityStatistic::class,
            $response[0]
        );
    }

    public function test_it_gets_a_list_of_activities_per_month_for_given_year()
    {
        $service = new ActivityStatisticService;
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        for ($i = 0; $i <= 2; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => '2017-01-02',
                'account_id' => $account->id,
            ]);
            $contact->activities()->attach($activity);
        }

        for ($i = 0; $i <= 5; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => '2017-02-01',
                'account_id' => $account->id,
            ]);
            $contact->activities()->attach($activity);
        }

        $response = $service->activitiesPerMonthForYear($contact, 2017);

        $this->assertCount(
            12,
            $response
        );

        $this->assertEquals(
            1,
            $response[0]['month']
        );

        $this->assertEquals(
            3,
            $response[0]['occurences']
        );

        $this->assertEquals(
            2,
            $response[1]['month']
        );

        $this->assertEquals(
            6,
            $response[1]['occurences']
        );

        $this->assertInstanceOf(
            Activity::class,
            $response[1]['activities'][0]
        );
    }
}
