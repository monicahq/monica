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

        $response = $this->get("/carddav/addressbooks/{$user->email}/contacts/{$contact->hashid()}.vcf");

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('PRODID:-//Sabre//Sabre VObject');
        $response->assertSee('FN:John Doe');
        $response->assertSee('N:Doe;John;;;');
        $response->assertSee('GENDER:O;');
    }

    /**
     * @group carddav
     */
    public function test_carddav_put_one_contact()
    {
        $user = $this->signin();

        $response = $this->call('PUT', "/carddav/addressbooks/{$user->email}/contacts/single_vcard_stub.vcf", [], [], [],
            ['content-type' => 'text/vcard; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * @group carddav
     */
    public function test_carddav_update_existing_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $filename = urlencode($contact->hashid().'.vcf');

        $response = $this->call('PUT', "/carddav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            ['content-type' => 'text/vcard; charset=utf-8'],
            "BEGIN:VCARD\nVERSION:3.0\nFN:John Doex\nN:Doex;John;;;\nEND:VCARD"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account->id,
            'first_name' => 'John',
            'last_name' => 'Doex',
        ]);
    }

    /**
     * @group carddav
     */
    public function test_carddav_update_existing_contact_no_modify()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $filename = urlencode($contact->hashid().'.vcf');

        $response = $this->get("/carddav/addressbooks/{$user->email}/contacts/{$filename}");
        $data = $response->getContent();

        $response = $this->call('PUT', "/carddav/addressbooks/{$user->email}/contacts/{$filename}", [], [], [],
            ['content-type' => 'text/vcard; charset=utf-8'],
            $data
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeader('ETag');
    }
}
