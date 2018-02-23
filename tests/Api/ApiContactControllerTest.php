<?php

namespace Tests\Feature;

use App\Contact;
use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_associates_tags_to_a_contact()
    {
        //$user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', "/contacts/{$contact->id}/setTags", [
            'tags' => ['family', 'test'],
        ]);

        $response
            ->assertStatus(200);

        $this->assertEquals(
            2,
            $contact->tags()->count()
        );
    }
}
