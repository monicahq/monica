<?php

namespace Tests\Api\DAV;

use Carbon\Carbon;
use Tests\ApiTestCase;
use Illuminate\Support\Str;
use App\Models\Contact\Task;
use App\Models\Contact\Contact;
use Sabre\VObject\PHPUnitAssertions;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VTodoTaskTest extends ApiTestCase
{
    use DatabaseTransactions, CardEtag, PHPUnitAssertions;

    /**
     * @group dav
     */
    public function test_caldav_get_one_task()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get("/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics");

        $response->assertStatus(200);
        $response->assertHeader('X-Sabre-Version');

        $this->assertVObjectEqualsVObject($this->getVTodo($task, true), $response->getContent() ?: $response->streamedContent());
    }

    /**
     * @group dav
     */
    public function test_caldav_put_one_task()
    {
        $user = $this->signin();

        $uuid = Str::uuid();

        $response = $this->call('PUT', "/dav/calendars/{$user->email}/tasks/{$uuid->toString()}.ics", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCALENDAR
BEGIN:VTODO
UID:{$uuid->toString()}
SUMMARY:title
DESCRIPTION:description
END:VTODO
END:VCALENDAR
"
        );

        $response->assertStatus(201);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account_id,
            'contact_id' => null,
            'uuid' => $uuid,
            'title' => 'title',
            'description' => 'description',
        ]);
    }

    /**
     * @group dav
     */
    public function test_caldav_update_existing_task()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => null,
        ]);

        $response = $this->call('PUT', "/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCALENDAR
BEGIN:VTODO
UID:{$task->uuid}
SUMMARY:new title
DESCRIPTION:new description
END:VTODO
END:VCALENDAR
"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account_id,
            'uuid' => $task->uuid,
            'title' => 'new title',
            'description' => 'new description',
        ]);
    }

    /**
     * @group dav
     */
    public function test_caldav_update_task_complete()
    {
        $user = $this->signin();
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => null,
        ]);

        $response = $this->call('PUT', "/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics", [], [], [],
            ['content-type' => 'application/xml; charset=utf-8'],
            "BEGIN:VCALENDAR
BEGIN:VTODO
UID:{$task->uuid}
SUMMARY:{$task->title}
DESCRIPTION:{$task->description}
STATUS:COMPLETED
COMPLETED:20190121T182800Z
END:VTODO
END:VCALENDAR
"
        );

        $response->assertStatus(204);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeaderMissing('ETag');

        $this->assertDatabaseHas('tasks', [
            'account_id' => $user->account_id,
            'uuid' => $task->uuid,
            'completed' => true,
            'completed_at' => Carbon::create(2019, 01, 21, 18, 28, 00),
        ]);
    }

    public function test_caldav_tasks_report()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $task = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'created_at' => now(),
        ]);

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/tasks/", [], [], [],
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

        $peopleurl = route('people.show', $contact);
        $sabreversion = \Sabre\VObject\Version::VERSION;

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/calendars/{$user->email}/tasks/{$task->uuid}.ics</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($task)}&quot;</d:getetag>".
              "<cal:calendar-data>{$this->getVTodo($task)}</cal:calendar-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>'.
        '</d:multistatus>', false);
    }

    public function test_caldav_tasks_report_multiget()
    {
        $user = $this->signin();
        $task1 = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'created_at' => now(),
        ]);
        $task2 = factory(Task::class)->create([
            'account_id' => $user->account_id,
            'created_at' => now(),
        ]);

        $response = $this->call('REPORT', "/dav/calendars/{$user->email}/tasks/", [], [], [],
            [
                'HTTP_DEPTH' => '1',
            ],
            "<cal:calendar-multiget xmlns:d=\"DAV:\" xmlns:cal=\"urn:ietf:params:xml:ns:caldav\">
               <d:prop>
                 <d:getetag />
                 <cal:calendar-data content-type=\"text/calendar\" version=\"2.0\" />
               </d:prop>
               <d:href>/dav/calendars/{$user->email}/tasks/{$task1->uuid}.ics</d:href>
               <d:href>/dav/calendars/{$user->email}/tasks/{$task2->uuid}.ics</d:href>
             </cal:calendar-multiget>"
        );

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">'.
        '<d:response>'.
          "<d:href>/dav/calendars/{$user->email}/tasks/{$task1->uuid}.ics</d:href>".
          '<d:propstat>'.
            '<d:prop>'.
              "<d:getetag>&quot;{$this->getEtag($task1)}&quot;</d:getetag>".
              "<cal:calendar-data>{$this->getVTodo($task1)}</cal:calendar-data>".
             '</d:prop>'.
             '<d:status>HTTP/1.1 200 OK</d:status>'.
           '</d:propstat>'.
          '</d:response>', false);
        $response->assertSee(
          '<d:response>'.
            "<d:href>/dav/calendars/{$user->email}/tasks/{$task2->uuid}.ics</d:href>".
            '<d:propstat>'.
              '<d:prop>'.
                "<d:getetag>&quot;{$this->getEtag($task2)}&quot;</d:getetag>".
                "<cal:calendar-data>{$this->getVTodo($task2)}</cal:calendar-data>".
               '</d:prop>'.
               '<d:status>HTTP/1.1 200 OK</d:status>'.
             '</d:propstat>'.
            '</d:response>'.
          '</d:multistatus>', false);
    }
}
