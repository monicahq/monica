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
            'date_it_happened' => $activityDate
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

        $note = factory(\App\Note::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'body' => 'this is a test'
        ]);

        // check that we can access the edit note view
        $response = $this->get('/people/'.$contact->id.'/notes/'.$note->id.'/edit');
        $response->assertStatus(200);

        // now edit the note
        $params = [
            'body' => 'this is another test'
        ];

        $this->put('/people/'.$contact->id.'/notes/'.$note->id, $params);

        // see if the change is in the database
        $new_params['account_id'] = $user->account_id;
        $new_params['contact_id'] = $contact->id;
        $new_params['id'] = $note->id;
        $new_params['body'] = 'this is another test';

        $this->assertDatabaseHas('notes', $new_params);

        // make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_type'] = 'note';
        $eventParams['object_id'] = $note->id;
        $eventParams['nature_of_operation'] = 'update';

        $this->assertDatabaseHas('events', $eventParams);

        // Visit the dashboard and checks that the note event appears on the
        // dashboard
        $response = $this->get('/dashboard');
        $response->assertSee('A note about John Doe has been updated');
        $response->assertSee('<a href="/people/'.$contact->id.'" id="note_update_'.$note->id.'">');
    }

    public function test_user_can_delete_a_note()
    {
        list($user, $contact) = $this->fetchUser();

        $note = factory(\App\Note::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'body' => 'this is a test'
        ]);

        $response = $this->get('/people/'.$contact->id);

        // make sure the link to delete the note is on the page
        $response->assertSee(
            'people/'.$contact->id.'/notes/'.$note->id.'/delete'
        );

        $response = $this->get('/people/'.$contact->id.'/notes/'.$note->id.'/delete');
        $response->assertStatus(302);

        $params['id'] = $note->id;

        $this->assertDatabaseMissing('notes', $params);

        // make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_id'] = $note->id;

        $this->assertDatabaseMissing('events', $eventParams);
    }
}
