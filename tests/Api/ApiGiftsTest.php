<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Gift;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiGiftsTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_git_get_contacts()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
            ]);
        $gift = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            ]);

        $response = $this->json('GET', '/api/gifts');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift->id,
        ]);
    }
}
