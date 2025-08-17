<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\SyncToken;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class CalDAVDatesTest extends TestCase
{
    use CardEtag, DatabaseTransactions;

    #[Test]
    #[Group('dav')]
    public function test_caldav_propfind_dates()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/dates-$vaultname");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/dates-$vaultname/</d:href>", false);
        $dateUuid = urlencode($date->uuid);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/dates-$vaultname/{$dateUuid}.ics</d:href>", false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_propfind_date_with_props()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/dates-$vaultname/", [], [], [],
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
              "<d:href>/dav/calendars/{$user->email}/dates-$vaultname/</d:href>".
              '<d:propstat>'.
                '<d:prop>'.
                  "<d:displayname>Contact important dates of $vault->name</d:displayname>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
              '</d:propstat>'.
            '</d:response>', false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_propfind_one_date()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}.ics");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}.ics</d:href>", false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_propfind_one_date_without_extension()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}</d:href>", false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_getctag()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/", [], [], [],
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

        $this->assertDatabaseHas('sync_tokens', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "dates-{$vault->id}",
        ]);

        $tokens = SyncToken::where([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "dates-{$vault->id}",
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/dates-$vaultname/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_getctag_dates()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}/dates-$vaultname/", [], [], [],
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
            'name' => "dates-{$vault->id}",
        ])->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">', false);
        $response->assertSee('<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/dates-$vaultname/</d:href>".
            '<d:propstat>'.
                '<d:prop>'.
                    "<cs:getctag>http://sabre.io/ns/sync/{$token->id}</cs:getctag>".
                    "<d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>', false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_sync_collection_with_token()
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 9, 0, 0));

        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 8, 0, 0));
        $token = SyncToken::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => "dates-{$vault->id}",
            'timestamp' => now(),
        ]);

        Carbon::setTestNow(Carbon::create(2020, 1, 1, 9, 0, 0));

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/dates-$vaultname/", [], [], [],
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
            'name' => "dates-{$vault->id}",
        ])
            ->orderBy('created_at')
            ->get()
            ->last();

        $response->assertSee(
            "<d:response>
  <d:href>/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}.ics</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($date)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>", false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_sync_collection_init()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/dates-$vaultname/", [], [], [],
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
            'name' => "dates-{$vault->id}",
        ])
            ->orderBy('created_at')
            ->get();

        $this->assertGreaterThan(0, $tokens->count());
        $token = $tokens->last();

        $response->assertSee(
            "<d:response>
  <d:href>/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}.ics</d:href>
  <d:propstat>
   <d:prop>
    <d:getetag>&quot;{$this->getEtag($date)}&quot;</d:getetag>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
 <d:sync-token>http://sabre.io/ns/sync/{$token->id}</d:sync-token>", false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_update_one_date()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'label' => 'Old Label',
        ]);
        $vaultname = rawurlencode($vault->name);

        $data = $this->getDate($date);
        $data = Str::replace('Old Label', 'New Label', $data);

        $response = $this->call('PUT', "/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}.ics", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            $data
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');

        $this->assertDatabaseHas('contact_important_dates', [
            'id' => $date->id,
            'label' => 'New Label',
        ]);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_put_one_date()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $vaultname = rawurlencode($vault->name);

        $data = 'BEGIN:VCALENDAR
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
VERSION:2.0
BEGIN:VEVENT
CREATED:20250814T000000Z
LAST-MODIFIED:20250814T000000Z
DTSTAMP:20250814T000000Z
UID:36ee6e82-5262-404f-aea1-98859c631892
SUMMARY:Réunion importante
DTSTART;VALUE=DATE:20250813
DTEND;VALUE=DATE:20250814
TRANSP:TRANSPARENT
END:VEVENT
END:VCALENDAR
';

        $response = $this->call('PUT', "/dav/calendars/{$user->email}/dates-$vaultname/36ee6e82-5262-404f-aea1-98859c631892.ics", [], [], [],
            [
                'content-type' => 'application/xml; charset=utf-8',
            ],
            $data
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');

        $this->assertDatabaseHas('contact_important_dates', [
            'label' => 'Réunion importante',
            'uuid' => '36ee6e82-5262-404f-aea1-98859c631892',
        ]);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_delete_one_date()
    {
        Carbon::setTestNow();
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create(['contact_id' => $contact->id]);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('DELETE', "/dav/calendars/{$user->email}/dates-$vaultname/{$date->uuid}.ics");

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');

        $this->assertDatabaseHas('contact_important_dates', [
            'id' => $date->id,
            'deleted_at' => now(),
        ]);
        $date->refresh();
        $this->assertTrue($date->trashed());
    }
}
