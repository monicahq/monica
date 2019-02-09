<?php

namespace Tests\Api\DAV;

use Tests\ApiTestCase;
use Illuminate\Support\Str;
use App\Models\Contact\Contact;
use Sabre\VObject\PHPUnitAssertions;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VEventBirthdayTest extends ApiTestCase
{
    use DatabaseTransactions, CardEtag, PHPUnitAssertions;

    /**
     * @group dav
     */
    public function test_caldav_get_one_birthday()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate = $contact->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate->uuid = Str::uuid();
        $specialDate->save();

        $response = $this->get("/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics");

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $this->assertVObjectEqualsVObject($this->getCal($specialDate, true), $response->getContent());
    }

    public function test_caldav_birthdays_report()
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
                'HTTP_DEPTH' => '1',
                'content-type' => 'application/xml; charset=utf-8',
            ],
            '<cal:calendar-query xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
               <d:prop>
                 <d:getetag />
                 <cal:calendar-data content-type="text/calendar" version="2.0" />
               </d:prop>
               <cal:filter>
                 <cal:comp-filter name="VCALENDAR" />
               </cal:filter>
             </cal:calendar-query>'
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate->uuid}.ics</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($specialDate)}&quot;</d:getetag>".
              "<cal:calendar-data>{$this->getCal($specialDate)}</cal:calendar-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus>');
    }

    public function test_caldav_birthdays_report_multiget()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $specialDate1 = $contact1->setSpecialDate('birthdate', 1983, 03, 04);
        $specialDate1->uuid = Str::uuid();
        $specialDate1->save();

        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
            'firstname' => 'Jane',
        ]);
        $specialDate2 = $contact2->setSpecialDate('birthdate', 1980, 05, 01);
        $specialDate2->uuid = Str::uuid();
        $specialDate2->save();

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/birthdays/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
            ],
            "<cal:calendar-multiget xmlns:d=\"DAV:\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\">
               <d:prop>
                 <d:getetag />
                 <cal:calendar-data content-type=\"text/calendar\" version=\"2.0\" />
               </d:prop>
               <d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate1->uuid}.ics</d:href>
               <d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate2->uuid}.ics</d:href>
             </cal:calendar-multiget>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate1->uuid}.ics</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($specialDate1)}&quot;</d:getetag>".
              "<cal:calendar-data>{$this->getCal($specialDate1)}</cal:calendar-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>');
        $response->assertSee(
          '<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/birthdays/{$specialDate2->uuid}.ics</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                "<d:getetag>&quot;{$this->getEtag($specialDate2)}&quot;</d:getetag>".
                "<cal:calendar-data>{$this->getCal($specialDate2)}</cal:calendar-data>".
               '</d:prop>'.
               '<d:status>HTTP/1.1 200 OK</d:status>'.
             '</d:propstat>'.
            '</d:response>'.
          '</d:multistatus>');
    }
}
