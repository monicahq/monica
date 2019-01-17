<?php

namespace Tests\Api\DAV;

use Tests\ApiTestCase;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DAVServerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * @group carddav
     */
    public function test_carddav_propfind_base()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/dav');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/</d:href>');
        $response->assertSee('<d:response><d:href>/dav/principals/</d:href>');
        $response->assertSee('<d:response><d:href>/dav/addressbooks/</d:href>');
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_principals()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/dav/principals');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/principals/</d:href>');
        $response->assertSee("<d:response><d:href>/dav/principals/{$user->email}/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_principals_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/principals/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/principals/{$user->email}/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_ensure_browser_plugin_not_enabled()
    {
        $user = $this->signin();

        $response = $this->call('GET', '/dav');

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

        $response = $this->call('PROPFIND', '/dav/addressbooks');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/addressbooks/</d:href>');
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_addressbooks_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/</d:href>");
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>");
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

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>");
        $contactId = urlencode($contact->uuid);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/{$contactId}.vcf</d:href>");
    }

    public function test_carddav_propfind_contacts_with_props()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

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
                "<d:displayname>{$user->name}</d:displayname>".
              '</d:prop>'.
              '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus');
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

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}</d:href>");
    }

    /**
     * @group carddav
     */
    public function test_carddav_propfind_groupmemberset()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('PROPFIND', "/dav/principals/{$user->email}/", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            '<propfind xmlns="DAV:"
                xmlns:CAL="urn:ietf:params:xml:ns:caldav"
                xmlns:CARD="urn:ietf:params:xml:ns:carddav">
                <prop>
                    <CARD:addressbook-home-set />
                    <group-member-set />
                </prop>
            </propfind>'
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
            '<d:response>'.
                "<d:href>/dav/principals/{$user->email}/</d:href>".
                '<d:propstat>'.
                    '<d:prop>'.
                        '<card:addressbook-home-set>'.
                            "<d:href>/dav/addressbooks/{$user->email}/</d:href>".
                        '</card:addressbook-home-set>'.
                        '<d:group-member-set>'.
                            "<d:href>/dav/principals/{$user->email}/</d:href>".
                        '</d:group-member-set>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
        '</d:multistatus>');
    }

    /**
     * @group carddav
     */
    public function test_carddav_report_propertysearch()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('REPORT', '/dav/principals/', [], [], [],
            [
                'HTTP_DEPTH' => '0',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<principal-property-search xmlns=\"DAV:\">
                <property-search>
                    <match>{$user->name}</match>
                    <prop>
                        <displayname/>
                    </prop>
                </property-search>
                <prop>
                    <displayname/>
                </prop>
            </principal-property-search>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
            '<d:response>'.
                "<d:href>/dav/principals/{$user->email}/</d:href>".
                '<d:propstat>'.
                    '<d:prop>'.
                        "<d:displayname>{$user->name}</d:displayname>".
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
        '</d:multistatus>');
    }

    public function test_carddav_getctag()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
                <prop>
                    <cs:getctag  />
                    <sync-token />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            ['account_id', $user->account_id],
            ['user_id', $user->id],
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">');
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'
        );
    }

    public function test_carddav_getctag_contacts()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'HTTP_DEPTH' => '0',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
                <prop>
                    <cs:getctag  />
                    <sync-token />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            ['account_id', $user->account_id],
            ['user_id', $user->id],
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">');
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/contacts/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'
        );
    }

    public function test_carddav_sync_collection_with_token()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
          'account_id' => $user->account->id,
        ]);
        $token = factory(SyncToken::class)->create([
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'timestamp' => \App\Helpers\DateHelper::parseDateTime(now()),
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<sync-collection xmlns='DAV:'>
                <sync-token>http://sabre.io/ns/sync/{$token->id}</sync-token>
                <sync-level>1</sync-level>
                <prop>
                    <getetag/>
                </prop>
            </sync-collection>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;");
        $response->assertSee("&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>");
    }

    public function test_carddav_sync_collection_init()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/contacts/", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<sync-collection xmlns='DAV:'>
                <sync-token />
                <sync-level>1</sync-level>
                <prop>
                    <getetag/>
                </prop>
            </sync-collection>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            ['account_id', $user->account_id],
            ['user_id', $user->id],
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/addressbooks/{$user->email}/contacts/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;");
        $response->assertSee("&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>");
    }
}
