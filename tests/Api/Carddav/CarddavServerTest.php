<?php

namespace Tests\Api\Carddav;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CarddavServerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * @group carddav
     */
    public function test_carddav_propfind_base()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/carddav');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/carddav/</d:href>');
        $response->assertSee('<d:response><d:href>/carddav/principals/</d:href>');
        $response->assertSee('<d:response><d:href>/carddav/addressbooks/</d:href>');
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_principals()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/carddav/principals');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/carddav/principals/</d:href>');
        $response->assertSee("<d:response><d:href>/carddav/principals/{$user->email}/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_principals_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/carddav/principals/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/carddav/principals/{$user->email}/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_ensure_browser_plugin_not_enabled()
    {
        $user = $this->signin();

        $response = $this->call('GET', '/carddav');

        $response->assertStatus(501);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('There was no plugin in the system that was willing to handle this GET method. Enable the Browser plugin to get a better result here.');
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_addressbooks()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/carddav/addressbooks');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/carddav/addressbooks/</d:href>');
        $response->assertSee("<d:response><d:href>/carddav/addressbooks/{$user->email}/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_addressbooks_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/carddav/addressbooks/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/carddav/addressbooks/{$user->email}/</d:href>");
        $response->assertSee("<d:response><d:href>/carddav/addressbooks/{$user->email}/contacts/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_contacts()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('PROPFIND', "/carddav/addressbooks/{$user->email}/contacts");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/carddav/addressbooks/{$user->email}/contacts/</d:href>");
        $contactId = urlencode(urlencode($contact->hashid()));
        $response->assertSee("<d:response><d:href>/carddav/addressbooks/{$user->email}/contacts/{$contactId}.vcf</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_one_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('PROPFIND', "/carddav/addressbooks/{$user->email}/contacts/{$contact->hashid()}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/carddav/addressbooks/{$user->email}/contacts/{$contact->hashid()}</d:href>");
    }
}
