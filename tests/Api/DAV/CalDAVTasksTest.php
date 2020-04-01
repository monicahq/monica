<?php

namespace Tests\Api\DAV;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Models\Contact\Task;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CalDAVTasksTest extends ApiTestCase
{
    use DatabaseTransactions, CardEtag;

    /**
     * @group dav
     */
    public function test_caldav_tasks_propfind()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/tasks");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/tasks/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics</d:href>", false);
    }

    public function test_caldav_tasks_propfind_with_props()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/tasks/", [], [], [],
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
            "<d:href>/dav/calendars/{$user->email}/tasks/</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                '<d:displayname>Tasks</d:displayname>'.
              '</d:prop>'.
              '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus', false);
    }

    /**
     * @group dav
     */
    public function test_caldav_tasks_propfind_one_task()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics</d:href>", false);
    }

    public function test_caldav_tasks_getctag()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

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
            'name' => 'tasks',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/tasks/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                    "<s:sync-token>http://sabre.io/ns/sync/{$token->id}</s:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    public function test_caldav_tasks_getctag_task()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/tasks/", [], [], [],
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
            'name' => 'tasks',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/tasks/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                    "<s:sync-token>http://sabre.io/ns/sync/{$token->id}</s:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    public function test_caldav_tasks_sync_collection_with_token()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 8, 0, 0));
        $token = factory(SyncToken::class)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'tasks',
            'timestamp' => now(),
        ]);

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/tasks/", [], [], [],
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
            'name' => 'tasks',
        ])
            ->orderBy('created_at')
            ->get()
            ->last();
        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($task)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>", false);
    }

    public function test_caldav_tasks_sync_collection_init()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/tasks/", [], [], [],
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
            'name' => 'tasks',
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee("<d:multistatus xmlns:d=\"DAV:\" xmlns:s=\"http://sabredav.org/ns\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\" xmlns:cs=\"http://calendarserver.org/ns/\">
 <d:response>
  <d:href>/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($task)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>
</d:multistatus>", false);
    }
}
