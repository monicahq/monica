<?php

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;
use App\Services\ActivityStatisticService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityStatisticServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_a_list_of_activities_since_a_given_number_of_months()
    {
        $service = new ActivityStatisticService;
        $contact = factory(Contact::class)->create();

        for ($i=0; $i<=2; $i++) {
            $activity = factory(Activity::class)->create([
                'date_it_happened' => Carbon::now()->subMonth(),
            ]);
            $contact->activities()->attach($activity);
        }

        $this->assertCount(
            3,
            $service->activitiesWithContactInTimeframe($contact, Carbon::now()->subMonths(2), Carbon::now())
        );

        $this->assertInstanceOf(
            Activity::class,
            $service->activitiesWithContactInTimeframe($contact, Carbon::now()->subMonths(2), Carbon::now())[1]
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
            $service->activitiesWithContactInTimeframe($contact, Carbon::now()->subMonths(2), Carbon::now())
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
        $response = $service->uniqueActivityTypesInTimeframe($contact, Carbon::now()->subMonths(2), Carbon::now());

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

        $contact->calculateActivitiesStatistics();
dd($contact->activityStatistics);
        $this->assertCount(
            2,
            $service->activitiesPerYearWithContact($contact)
        );
    }
}
