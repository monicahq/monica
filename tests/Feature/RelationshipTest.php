<?php

namespace Tests\Feature;

use App\Contact;
use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends FeatureTestCase
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

    public function test_user_can_see_add_relationship_form()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->id.'/relationships/add');

        $response->assertSee(
            'Add significant other'
        );
    }

    public function test_user_can_add_a_partial_contact_as_relationship()
    {
        list($user, $contact) = $this->fetchUser();

        $this->post(
            route('people.relationships.store', $contact), [
                'first_name' => 'Jessica',
                'is_birthdate_approximate' => 'unknown',
        ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jessica',
            'account_id' => $user->account_id,
            'is_partial' => 1,
        ]);

        $response = $this->get('people/'.$contact->id);
        $response->assertSee('Jessica');
        $response->assertSee($contact->id.'-edit-relationship');
    }

    public function test_user_can_add_a_real_contact_as_relationship()
    {
        list($user, $contact) = $this->fetchUser();

        $this->post(
            route('people.relationships.store', $contact), [
                'first_name' => 'Jessica',
                'is_birthdate_approximate' => 'unknown',
                'realContact' => 1,
        ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jessica',
            'account_id' => $user->account_id,
            'is_partial' => 0,
        ]);

        $response = $this->get('people/'.$contact->id);
        $response->assertSee('Jessica');
        $response->assertSee($contact->id.'-unlink-relationship');
    }
}
