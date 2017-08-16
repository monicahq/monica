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

        $response = $this->get('/people/'.$contact->id.'/activities/add');

        $response->assertStatus(200);

        $response->assertSee(
            'What did you do with John?'
        );
    }

    public function test_user_can_add_an_activity()
    {
        list($user, $contact) = $this->fetchUser();

        $activityTitle = 'This is the title';
        $activityDate = \Carbon\Carbon::now();

        $params = [
            'summary' => $activityTitle,
            'date_it_happened' => $activityDate,
        ];

        $response = $this->post('/people/'.$contact->id.'/activities/store', $params);
        $response->assertRedirect('/people/'.$contact->id);

        // Assert the note has been added for the correct user.
        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['summary'] = $activityTitle;
        $params['date_it_happened'] = $activityDate;

        $this->assertDatabaseHas('activities', $params);

        // Check that the Contact view contains the newly created note
        $response = $this->get('people/'.$contact->id);
        $response->assertSee($activityTitle);

        // Make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_type'] = 'activity';
        $eventParams['nature_of_operation'] = 'create';
        $this->assertDatabaseHas('events', $eventParams);

        // Visit the dashboard and checks that the note event appears on the
        // dashboard
        $response = $this->get('/dashboard');
        $response->assertSee('An activity about John Doe has been added');
        $response->assertSee('<a href="/people/'.$contact->id.'" id="activity_create');
    }

    public function test_user_can_edit_an_activity()
    {
        list($user, $contact) = $this->fetchUser();

        $activity = factory(\App\Activity::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'summary' => 'This is the title',
            'date_it_happened' => \Carbon\Carbon::now(),
        ]);

        // check that we can access the edit activity view
        $response = $this->get('/people/'.$contact->id.'/activities/'.$activity->id.'/edit');
        $response->assertStatus(200);

        // now edit the activity
        $params = [
            'summary' => 'this is another test',
            'date_it_happened' => \Carbon\Carbon::now(),
            'activity_type_id' => null,
            'description' => null,
        ];

        $this->put('/people/'.$contact->id.'/activities/'.$activity->id, $params);

        // see if the change is in the database
        $newParams['account_id'] = $user->account_id;
        $newParams['contact_id'] = $contact->id;
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

        // Visit the dashboard and checks that the note event appears on the
        // dashboard
        $response = $this->get('/dashboard');
        $response->assertSee('An activity about John Doe has been updated');
        $response->assertSee('<a href="/people/'.$contact->id.'" id="activity_update_'.$activity->id.'">');
    }

    public function test_user_can_delete_an_activity()
    {
        list($user, $contact) = $this->fetchUser();

        $activity = factory(\App\Activity::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'summary' => 'This is the title',
            'date_it_happened' => \Carbon\Carbon::now(),
        ]);

        $response = $this->get('/people/'.$contact->id);

        $response = $this->delete('/people/'.$contact->id.'/activities/'.$activity->id);
        $response->assertStatus(302);

        $params['id'] = $activity->id;

        $this->assertDatabaseMissing('activities', $params);

        // make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_id'] = $activity->id;

        $this->assertDatabaseMissing('events', $eventParams);
    }
}
