<?php

namespace Tests\Api\Carddav;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CarddavContactTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * @group carddav
     */
    public function test_carddav_get_one_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->get("/carddav/addressbooks/{$user->email}/Contacts/{$contact->hashid()}");

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('PRODID:-//Sabre//Sabre VObject');
        $response->assertSee('FN:'.$contact->name);
        $response->assertSee('N:'.$contact->last_name.';'.$contact->first_name);
        $response->assertSee('GENDER:O;');
    }
}
