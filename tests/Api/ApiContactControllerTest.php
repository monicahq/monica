<?php

namespace Tests\Feature;

use Laravel\Passport\Passport;
use App\Contact;
use App\User;
use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_tags_are_required_to_associate_tags_to_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/setTags");

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'message' => ['The tags field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_associates_tags_to_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/setTags", [
                            'tags' => ['very-specific-tag-name', 'very-specific-tag-name-2'],
                        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $contact->id,
            'name' => 'very-specific-tag-name',
            'name' => 'very-specific-tag-name-2',
        ]);
    }
}
