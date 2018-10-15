<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Call;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CallTest extends FeatureTestCase
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

    public function test_user_sees_empty_state_when_no_calls_are_available()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID());

        $response->assertStatus(200);

        // is the default blank state present?
        $response->assertSee(
            'Keep track of the phone calls'
        );

        // is the button to log a call present?
        $response->assertSee('Log a call');
    }

    public function test_user_sees_add_call_modal()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID());

        $response->assertStatus(200);

        $response->assertSee(
            'What did you talk about? (optional)'
        );
    }

    public function test_user_can_add_a_call_without_a_description()
    {
        list($user, $contact) = $this->fetchUser();

        $params = [
            'called_at' => '2013-01-01',
            'content' => null,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/call/store', $params);
        $response->assertRedirect('/people/'.$contact->hashID());

        // Assert the call has been added for the correct user.
        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['called_at'] = '2013-01-01 00:00:00';

        $this->assertDatabaseHas('calls', $params);

        // Check that the Contact view contains the newly created call
        $response = $this->get('people/'.$contact->hashID());
        $response->assertSee('Jan 01, 2013');
    }

    public function test_user_can_add_a_call_with_a_description()
    {
        list($user, $contact) = $this->fetchUser();

        $params = [
            'called_at' => '2013-01-01',
            'content' => 'This is a test call',
        ];

        $response = $this->post('/people/'.$contact->hashID().'/call/store', $params);
        $response->assertRedirect('/people/'.$contact->hashID());

        // Assert the call has been added for the correct user.
        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['called_at'] = '2013-01-01 00:00:00';

        $this->assertDatabaseHas('calls', $params);

        // Check that the Contact view contains the newly created call
        $response = $this->get('people/'.$contact->hashID());
        $response->assertSee('Jan 01, 2013');
        $response->assertSee('This is a test call');
    }

    public function test_user_can_delete_a_call()
    {
        list($user, $contact) = $this->fetchUser();

        $call = factory(Call::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'content' => 'this is a test',
            'called_at' => '2013-01-01 00:00:00',
        ]);

        $response = $this->get('/people/'.$contact->hashID());

        $response = $this->delete('/people/'.$contact->hashID().'/call/'.$call->id);
        $response->assertStatus(302);

        $params = [];

        $params['id'] = $call->id;

        $this->assertDatabaseMissing('calls', $params);
    }
}
