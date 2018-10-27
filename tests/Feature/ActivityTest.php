<?php

namespace Tests\Feature;

use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
     * @return array
     */
    private function fetchUser()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        return [$user, $contact];
    }

    public function test_user_sees_empty_state_when_no_activities_are_available()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID());

        $response->assertStatus(200);

        // is the default blank state present?
        $response->assertSee(
            'Add an activity'
        );

        // is the button to add a note present?
        $response->assertSee('Add activity');
    }

    public function test_user_sees_add_activity_screen()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/activities/add/'.$contact->hashID());

        $response->assertStatus(200);

        $response->assertSee(
            'What did you do with John?'
        );
    }

    public function test_user_can_add_an_activity()
    {
        list($user, $contact) = $this->fetchUser();

        $activityTitle = 'This is the title';
        $activityDate = now();

        $response = $this->addActivity($contact, $activityTitle, $activityDate);
        $response->assertRedirect('/people/'.$contact->hashID());

        // Assert the activity has been added
        $params = [];
        $params['account_id'] = $user->account_id;
        $params['summary'] = $activityTitle;
        $params['date_it_happened'] = $activityDate;

        $this->assertDatabaseHas('activities', $params);

        // Get the activity that we just created
        // and make sure it's in our pivot table
        $latestActivity = Activity::all('id')->last();

        $this->assertDatabaseHas('activity_contact', [
            'contact_id' => $contact->id,
            'activity_id' => $latestActivity->id,
        ]);

        // Check that the Contact view contains the newly created note
        $response = $this->get('/people/'.$contact->hashID());
        $response->assertSee($activityTitle);
    }

    public function test_add_an_activity_account_mismatch()
    {
        list($user1, $contact1) = $this->fetchUser();
        $account2 = factory(Account::class)->create();
        $user2 = factory(User::class)->create([
            'account_id' => $account2->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $account2->id,
        ]);

        $activityTitle = 'This is the title';
        $activityDate = now();

        $params = [
            'summary' => $activityTitle,
            'date_it_happened' => $activityDate,
        ];

        $response = $this->post('/activities/store/'.$contact1->hashID(), $params + ['contacts' => [$contact2->id]]);

        // Assert the activity is missing
        $params['account_id'] = $user1->account_id;
        $this->assertDatabaseMissing('activities', $params);

        $params['account_id'] = $user2->account_id;
        $this->assertDatabaseMissing('activities', $params);
    }

    public function test_add_an_activity_account_mismatch_return_notfound()
    {
        list($user1, $contact1) = $this->fetchUser();
        $account2 = factory(Account::class)->create();
        $user2 = factory(User::class)->create([
            'account_id' => $account2->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $account2->id,
        ]);

        $activityTitle = 'This is the title';
        $activityDate = now();

        $params = [
            'summary' => $activityTitle,
            'date_it_happened' => $activityDate,
        ];

        $response = $this->post('/activities/store/'.$contact1->hashID(), $params + ['contacts' => [$contact2->id]]);
        $response->assertStatus(302);
    }

    public function test_user_can_edit_an_activity()
    {
        list($user, $contact) = $this->fetchUser();

        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
            'summary' => 'This is the title',
            'date_it_happened' => now(),
        ]);

        // Attach the created activity to the current user to make this an update
        $contact->activities()->save($activity);

        // check that we can access the edit activity view
        $response = $this->get('/activities/'.$activity->hashID().'/edit/'.$contact->hashID());
        $response->assertStatus(200);

        // now edit the activity
        $params = [
            'contacts' => [$contact->id],
            'summary' => 'this is another test',
            'date_it_happened' => now(),
            'activity_type_id' => null,
            'description' => null,
        ];

        $response = $this->put('/activities/'.$activity->hashID().'/'.$contact->hashID(), $params);
        $response->assertRedirect('/people/'.$contact->hashID());

        $newParams = [];

        // see if the change is in the database
        $newParams['account_id'] = $user->account_id;
        $newParams['id'] = $activity->id;
        $newParams['summary'] = 'this is another test';

        $this->assertDatabaseHas('activities', $newParams);
    }

    public function test_user_doesnt_see_activity_report_link_when_no_activities_are_available()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID());

        $response->assertStatus(200);

        // is the default blank state present?
        $response->assertDontSee(
            'View activities report'
        );
    }

    public function test_user_see_activity_report_link_when_activities_are_available()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID());

        $response->assertStatus(200);

        // is the default blank state present?
        $response->assertDontSee(
            'View activities report'
        );
    }

    public function test_user_see_activity_report_page()
    {
        list($user, $contact) = $this->fetchUser();

        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
            'summary' => 'This is the title',
            'date_it_happened' => '1990-01-05',
        ]);
        $contact->activities()->save($activity);

        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
            'summary' => 'This is a second title',
            'date_it_happened' => '2000-01-05',
        ]);
        $contact->activities()->save($activity);

        $contact->calculateActivitiesStatistics();

        $response = $this->followingRedirects()
                ->get('/people/'.$contact->hashID().'/activities');

        $response->assertStatus(200);

        $response->assertSee(
            'Here is what you two have done in 2000'
        );

        $response->assertSee(
            'This is a second title'
        );

        $response = $this->get('/people/'.$contact->hashID().'/activities/1990');

        $response->assertStatus(200);

        $response->assertSee(
            'Here is what you two have done in 1990'
        );

        $response->assertSee(
            'This is the title'
        );
    }

    /* private methods */
    private function addActivity($contact, $title, $date)
    {
        $params = [
            'summary' => $title,
            'date_it_happened' => $date,
        ];

        return $this->post('/activities/store/'.$contact->hashID(), $params + ['contacts' => [$contact->id]]);
    }
}
