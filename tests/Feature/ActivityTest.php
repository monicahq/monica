<?php

namespace Tests\Feature;

use App\Contact;
use Tests\FeatureTestCase;
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

        $response = $this->get('/people/'.$contact->id);

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

        $response = $this->get('/activities/add/'.$contact->id);

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

        $params = [
            'summary' => $activityTitle,
            'date_it_happened' => $activityDate,
        ];

        $response = $this->post('/activities/store/'.$contact->id, $params + ['contacts' => [$contact->id]]);
        $response->assertRedirect('/people/'.$contact->id);

        // Assert the activity has been added
        $params['account_id'] = $user->account_id;
        $params['summary'] = $activityTitle;
        $params['date_it_happened'] = $activityDate;

        $this->assertDatabaseHas('activities', $params);

        // Get the activity that we just created
        // and make sure it's in our pivot table
        $latestActivity = \App\Activity::all('id')->last();

        $this->assertDatabaseHas('activity_contact', [
            'contact_id' => $contact->id,
            'activity_id' => $latestActivity->id,
        ]);

        // Make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_type'] = 'activity';
        $eventParams['nature_of_operation'] = 'create';
        $this->assertDatabaseHas('events', $eventParams);

        // Check that the Contact view contains the newly created note
        $response = $this->get('/people/'.$contact->id);
        $response->assertSee($activityTitle);
    }

    public function test_user_can_edit_an_activity()
    {
        list($user, $contact) = $this->fetchUser();

        $activity = factory(\App\Activity::class)->create([
            'account_id' => $user->account_id,
            'summary' => 'This is the title',
            'date_it_happened' => now(),
        ]);

        // Attach the created activity to the current user to make this an update
        $contact->activities()->save($activity);

        // check that we can access the edit activity view
        $response = $this->get('/activities/'.$activity->id.'/edit/'.$contact->id);
        $response->assertStatus(200);

        // now edit the activity
        $params = [
            'contacts' => [$contact->id],
            'summary' => 'this is another test',
            'date_it_happened' => now(),
            'activity_type_id' => null,
            'description' => null,
        ];

        $this->put('/activities/'.$activity->id.'/'.$contact->id, $params);

        // see if the change is in the database
        $newParams['account_id'] = $user->account_id;
        $newParams['id'] = $activity->id;
        $newParams['summary'] = 'this is another test';

        $this->assertDatabaseHas('activities', $newParams);

        // make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_type'] = 'activity';
        $eventParams['object_id'] = $activity->id;
        $eventParams['nature_of_operation'] = 'update';

        $this->assertDatabaseHas('events', $eventParams);
    }
}
