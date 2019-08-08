<?php

namespace Tests\Api\DAV;

use Carbon\Carbon;
use Tests\ApiTestCase;
use Illuminate\Support\Str;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CalDAVBirthdaysTest extends ApiTestCase
{
    use DatabaseTransactions, CardEtag;

    /**
     * @group dav
     */
    public function test_caldav_birthdays_propfind()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/birthdays");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/birthdays/</d:href>");
        $specialDate->refresh();
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics</d:href>");
    }

    public function test_caldav_birthdays_propfind_with_props()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/birthdays/", [], [], [],
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
            "<d:href>/dav/calendars/{$user->email}/birthdays/</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                '<d:displayname>Birthdays</d:displayname>'.
              '</d:prop>'.
              '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus');
    }

    /**
     * @group dav
     */
    public function test_caldav_birthdays_propfind_one_birthday()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate->uuid = Str::uuid();
        $specialDate->save();

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics</d:href>");
    }

    public function test_caldav_birthdays_getctag()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate->uuid = Str::uuid();
        $specialDate->save();

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/' xmlns:s='http://sabredav.org/ns'>
                <prop>
                    <cs:getctag />
                    <sync-token />
                    <s:sync-token />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'birthdays',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">');
        $response->assertSee('<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/birthdays/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                    "<s:sync-token>http://sabre.io/ns/sync/{$token->id}</s:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'
        );
    }

    public function test_caldav_birthdays_getctag_birthday()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate->uuid = Str::uuid();
        $specialDate->save();

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/birthdays/", [], [], [],
            [
                'HTTP_DEPTH' => '0',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            "<propfind xmlns='DAV:' xmlns:cs='http://calendarserver.org/ns/' xmlns:s='http://sabredav.org/ns'>
                <prop>
                    <cs:getctag />
                    <sync-token />
                    <s:sync-token />
                </prop>
            </propfind>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'birthdays',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">');
        $response->assertSee('<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/birthdays/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                    "<s:sync-token>http://sabre.io/ns/sync/{$token->id}</s:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'
        );
    }

    public function test_caldav_birthdays_sync_collection_with_token()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
          'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate->uuid = Str::uuid();
        $specialDate->save();

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 8, 0, 0));
        $token = factory(SyncToken::class)->create([
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'name' => 'birthdays',
            'timestamp' => now(),
        ]);

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/birthdays/", [], [], [],
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

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($specialDate)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>");
    }

    public function test_caldav_birthdays_sync_collection_init()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate->uuid = Str::uuid();
        $specialDate->save();

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/birthdays/", [], [], [],
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
            'name' => 'birthdays',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($specialDate)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>");
    }
}
