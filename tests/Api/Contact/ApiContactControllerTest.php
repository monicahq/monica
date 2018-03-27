<?php

namespace Tests\Api\Contact;

use App\Contact;
use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_gets_a_list_of_contacts()
    {
        $user = $this->signin();

        $contact = factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );
    }

    public function test_it_gets_a_list_of_contacts_with_pagination_everytime()
    {
        $user = $this->signin();

        $contact = factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        $contact = factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contacts?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/contacts?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_is_possible_to_search_for_a_specific_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        // create 10 other contacts named Bob (to avoid random conflicts if we took a random name)
        $contact = factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
            'first_name' => 'bob',
        ]);

        $response = $this->json('GET', '/api/contacts?query=ro');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'first_name' => 'roger',
            'total' => 1,
            'query' => 'ro',
        ]);
    }
}
