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
}
