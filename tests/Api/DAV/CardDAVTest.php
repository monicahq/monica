<?php

namespace Tests\Api\DAV;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CardDAVTest extends ApiTestCase
{
    use DatabaseTransactions, CardEtag;

    /**
     * @group dav
     */
    public function test_carddav_propfind_addressbooks()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/dav/addressbooks');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/addressbooks/</d:href>', false);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_carddav_propfind_addressbooks_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_carddav_propfind_contacts()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>", false);
        $contactId = urlencode($contact->uuid);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/{$contactId}.vcf</d:href>", false);
    }

    public function test_carddav_propfind_contacts_with_props()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => 0,
            ],
            '<d:propfind xmlns:d="DAV:">
               <d:prop>
                 <d:displayname />
               </d:prop>
             </d:propfind>'
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
          '<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                '<d:displayname>Contacts</d:displayname>'.
              '</d:prop>'.
              '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus', false);
    }

    /**
     * @group dav
     */
    public function test_carddav_propfind_one_contact()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_carddav_propfind_one_contact_without_extension()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}</d:href>", false);
    }

    public function test_carddav_getctag()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
                <prop>
                    <cs:getctag />
                    <sync-token />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    public function test_carddav_get_me_card()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $user->me_contact_id = $contact->id;
        $user->save();

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}", [], [], [],
            [
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
                <prop>
                    <cs:me-card />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:me-card>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</cs:me-card>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    public function test_carddav_set_me_card()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PROPPATCH', "/dav/addressbooks/{$user->email}/contacts", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propertyupdate xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
                <set>
                    <prop>
                        <cs:me-card>
                            <href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</href>
                        </cs:me-card>
                    </prop>
                </set>
            </propertyupdate>"
        );

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    '<cs:me-card/>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'me_contact_id' => $contact->id,
        ]);
    }

    public function test_carddav_getctag_contacts()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => '0',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
                <prop>
                    <cs:getctag />
                    <sync-token />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    public function test_carddav_sync_collection_with_token()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 8, 0, 0));
        $token = factory(SyncToken::class)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now(),
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<sync-collection xmlns='DAV:'>
                <sync-token>http://sabre.io/ns/sync/{$token->id}</sync-token>
                <sync-level>1</sync-level>
                <prop>
                    <getetag />
                </prop>
            </sync-collection>"
        );

        $response->assertStatus(207);

        $token = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ])
            ->orderBy('created_at')
            ->get()
            ->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>", false);
    }

    public function test_carddav_sync_collection_init()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<sync-collection xmlns='DAV:'>
                <sync-token />
                <sync-level>1</sync-level>
                <prop>
                    <getetag />
                </prop>
            </sync-collection>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>", false);
    }

    public function test_carddav_sync_collection_deleted_contact()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'deleted_at' => Carbon::create(2019, 3, 1, 9, 0, 0),
        ]);

        Carbon::setTestNow(Carbon::create(2019, 2, 1, 9, 0, 0));
        $token = factory(SyncToken::class)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now(),
        ]);

        Carbon::setTestNow(Carbon::create(2019, 4, 1, 9, 0, 0));

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<sync-collection xmlns='DAV:'>
                <sync-token>http://sabre.io/ns/sync/{$token->id}</sync-token>
                <sync-level>1</sync-level>
                <prop>
                    <getetag />
                </prop>
            </sync-collection>"
        );

        $response->assertStatus(207);

        $token = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ])
            ->orderBy('created_at')
            ->get()
            ->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:status>HTTP/1.1 404 Not Found</d:status>
  <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop/>
   <d:status>HTTP/1.1 418 I'm a teapot</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>", false);
    }
}
