<?php

namespace Tests\Feature;

use App\Contact;
use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NoteTest extends FeatureTestCase
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

    public function test_user_sees_empty_state_when_no_notes_are_available()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->id);

        $response->assertStatus(200);

        // is the default blank state present?
        $response->assertSee(
            'Add a note'
        );

        // is the button to add a note present?
        $response->assertSee('Add another note');
    }

    public function test_user_sees_add_note_screen()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->id.'/notes/add');

        $response->assertStatus(200);

        $response->assertSee(
            'Add a note about John'
        );
    }

    public function test_user_can_add_a_note()
    {
        list($user, $contact) = $this->fetchUser();

        $noteBody = 'This is a note that I would like to see';

        $params = [
            'body' => $noteBody,
        ];

        $response = $this->post('/people/'.$contact->id.'/notes/store', $params);
        $response->assertRedirect('/people/'.$contact->id);

        // Assert the note has been added for the correct user.
        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['body'] = $noteBody;

        $this->assertDatabaseHas('notes', $params);

        // Check that the Contact view contains the newly created note
        $response = $this->get('people/'.$contact->id);
        $response->assertSee($noteBody);

        // Make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_type'] = 'note';
        $eventParams['nature_of_operation'] = 'create';
        $this->assertDatabaseHas('events', $eventParams);

        // Visit the dashboard and checks that the note event appears on the
        // dashboard
        $response = $this->get('/dashboard');
        $response->assertSee('A note about John Doe has been added');
        $response->assertSee('<a href="/people/'.$contact->id.'" id="note_create');
    }

    public function test_user_can_edit_a_note()
    {
        list($user, $contact) = $this->fetchUser();

        $note = factory(\App\Note::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'body' => 'this is a test',
        ]);

        // check that we can access the edit note view
        $response = $this->get('/people/'.$contact->id.'/notes/'.$note->id.'/edit');
        $response->assertStatus(200);

        // now edit the note
        $params = [
            'body' => 'this is another test',
        ];

        $this->put('/people/'.$contact->id.'/notes/'.$note->id, $params);

        // see if the change is in the database
        $newParams['account_id'] = $user->account_id;
        $newParams['contact_id'] = $contact->id;
        $newParams['id'] = $note->id;
        $newParams['body'] = 'this is another test';

        $this->assertDatabaseHas('notes', $newParams);

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
            'body' => 'this is a test',
        ]);

        $response = $this->get('/people/'.$contact->id);

        $response = $this->delete('/people/'.$contact->id.'/notes/'.$note->id);
        $response->assertStatus(302);

        $params['id'] = $note->id;

        $this->assertDatabaseMissing('notes', $params);

        // make sure no event is in the database about this object
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_id'] = $note->id;

        $this->assertDatabaseMissing('events', $eventParams);
    }
}
