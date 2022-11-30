<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Models\Contact;
use App\Models\SyncToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class CardDAVTest extends TestCase
{
    use DatabaseTransactions, CardEtag;

    /**
     * @test
     * @group dav
     */
    public function test_carddav_propfind_addressbooks()
    {
        $user = $this->createUser();

        $response = $this->call('PROPFIND', '/dav/addressbooks');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/addressbooks/</d:href>', false);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/</d:href>", false);
    }

    /**
     * @test
     * @group dav
     */
    public function test_carddav_propfind_addressbooks_user()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/$vaultname/</d:href>", false);
    }

    /**
     * @test
     * @group dav
     */
    public function test_carddav_propfind_contacts()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/$vaultname");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/$vaultname/</d:href>", false);
        $contactId = urlencode($contact->uuid);
        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contactId}.vcf</d:href>", false);
    }

    public function test_carddav_propfind_contacts_with_props()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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

        $response->assertSee(
            '<d:response>'.
              "<d:href>/dav/addressbooks/{$user->email}/$vaultname/</d:href>".
              '<d:propstat>'.
                '<d:prop>'.
                  '<d:displayname>Contacts</d:displayname>'.
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
              '</d:propstat>'.
            '</d:response>', false);
    }

    /**
     * @test
     * @group dav
     */
    public function test_carddav_propfind_one_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf</d:href>", false);
    }

    /**
     * @test
     * @group dav
     */
    public function test_carddav_propfind_one_contact_without_extension()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}</d:href>", false);
    }

    public function test_carddav_getctag()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

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

        $this->assertDatabaseHas('synctokens', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "contacts-{$vault->uuid}",
        ]);

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "contacts-{$vault->uuid}",
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/$vaultname/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<x1:getctag xmlns:x1=\"http://calendarserver.org/ns/\">http://sabre.io/ns/sync/{$token->id}</x1:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    // public function test_carddav_get_me_card()
    // {
    //     $user = $this->createUser();
    //     $vault = $this->createVaultUser($user);
    //     $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
    //     $vaultname = rawurlencode($vault->name);
    //     $user->me_contact_id = $contact->id;
    //     $user->save();

    //     $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}", [], [], [],
    //         [
    //             'HTTP_DEPTH' => '1',
    //             'content-type' => 'application/xml; charset=utf-8',
    //         ],
    //         "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
    //             <prop>
    //                 <cs:me-card />
    //             </prop>
    //         </propfind>"
    //     );

    //     $response->assertStatus(207);
    //     $response->assertHeader('X-Sabre-Version');

    //     $response->assertSee('<d:response>'.
    //         "<d:href>/dav/addressbooks/{$user->email}/$vaultname/</d:href>".
    //         '<d:propstat>'.
    //             '<d:prop>'.
    //                 "<cs:me-card>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf</cs:me-card>".
    //             '</d:prop>'.
    //             '<d:status>HTTP/1.1 200 OK</d:status>'.
    //         '</d:propstat>'.
    //     '</d:response>', false);
    // }

    // public function test_carddav_set_me_card()
    // {
    //     $user = $this->createUser();
    //     $vault = $this->createVaultUser($user);
    //     $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
    //     $vaultname = rawurlencode($vault->name);

    //     $response = $this->call('PROPPATCH', "/dav/addressbooks/{$user->email}/contacts", [], [], [],
    //         [
    //             'content-type' => 'application/xml; charset=utf-8',
    //         ],
    //         "<propertyupdate xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/'>
    //             <set>
    //                 <prop>
    //                     <cs:me-card>
    //                         <href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf</href>
    //                     </cs:me-card>
    //                 </prop>
    //             </set>
    //         </propertyupdate>"
    //     );

    //     $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">', false);
    //     $response->assertSee('<d:response>'.
    //         "<d:href>/dav/addressbooks/{$user->email}/contacts</d:href>".
    //         '<d:propstat>'.
    //             '<d:prop>'.
    //                 '<cs:me-card/>'.
    //             '</d:prop>'.
    //             '<d:status>HTTP/1.1 200 OK</d:status>'.
    //         '</d:propstat>'.
    //     '</d:response>', false);

    //     $this->assertDatabaseHas('users', [
    //         'id' => $user->id,
    //         'me_contact_id' => $contact->id,
    //     ]);
    // }

    public function test_carddav_getctag_contacts()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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
            'name' => "contacts-{$vault->uuid}",
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/addressbooks/{$user->email}/$vaultname/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<x1:getctag xmlns:x1=\"http://calendarserver.org/ns/\">http://sabre.io/ns/sync/{$token->id}</x1:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    public function test_carddav_sync_collection_with_token()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 8, 0, 0));
        $token = SyncToken::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "contacts-{$vault->uuid}",
            'timestamp' => now(),
        ]);

        Carbon::setTestNow(Carbon::create(2020, 1, 1, 9, 0, 0));

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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
            'name' => "contacts-{$vault->uuid}",
        ])
            ->orderBy('created_at')
            ->get()
            ->last();

        $response->assertSee(
            "<d:response>
  <d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>", false);
    }

    public function test_carddav_sync_collection_init()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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
            'name' => "contacts-{$vault->uuid}",
        ])
            ->orderBy('created_at')
            ->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee(
            "<d:response>
  <d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($contact)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>", false);
    }

    public function test_carddav_sync_collection_deleted_contact()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'deleted_at' => Carbon::create(2019, 3, 1, 9, 0, 0),
        ]);
        $vaultname = rawurlencode($vault->name);

        Carbon::setTestNow(Carbon::create(2019, 2, 1, 9, 0, 0));
        $token = SyncToken::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "contacts-{$vault->uuid}",
            'timestamp' => now(),
        ]);

        Carbon::setTestNow(Carbon::create(2019, 4, 1, 9, 0, 0));

        $response = $this->call('REPORT', "/dav/addressbooks/{$user->email}/$vaultname/", [], [], [],
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
            'name' => "contacts-{$vault->uuid}",
        ])
            ->orderBy('created_at')
            ->get()
            ->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\">
 <d:response>
  <d:status>HTTP/1.1 404 Not Found</d:status>
  <d:href>/dav/addressbooks/{$user->email}/$vaultname/{$contact->uuid}.vcf</d:href>
  <d:propstat>
   <d:prop/>
   <d:status>HTTP/1.1 418 I'm a teapot</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>", false);
    }
}
