<?php

namespace Tests\Api\DAV;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DAVServerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * @group dav
     */
    public function test_dav_propfind_base()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/dav');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/</d:href>', false);
        $response->assertSee('<d:response><d:href>/dav/principals/</d:href>', false);
        $response->assertSee('<d:response><d:href>/dav/addressbooks/</d:href>', false);
        $response->assertSee('<d:response><d:href>/dav/calendars/</d:href>', false);
    }

    /**
     * @group dav
     */
    public function test_dav_propfind_principals()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/dav/principals');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/principals/</d:href>', false);
        $response->assertSee("<d:response><d:href>/dav/principals/{$user->email}/</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_dav_propfind_principals_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/principals/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/principals/{$user->email}/</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_dav_ensure_browser_plugin_not_enabled()
    {
        $user = $this->signin();

        $response = $this->call('GET', '/dav');

        $response->assertStatus(302);
        $response->assertHeader('X-Sabre-Version');
        $response->assertHeader('Location', route('settings.dav'));
    }

    /**
     * @group dav
     */
    public function test_carddav_propfind_groupmemberset()
    {
        $user = $this->signin();

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
        '</d:multistatus>', false);
    }

    /**
     * @group dav
     */
    public function test_carddav_report_propertysearch()
    {
        $user = $this->signin();

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
        '</d:multistatus>', false);
    }

    /**
     * @group dav
     */
    public function test_caldav_propfind()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', '/dav/calendars');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/calendars/</d:href>', false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_caldav_propfind_calendars_user()
    {
        $user = $this->signin();

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/birthdays/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/tasks/</d:href>", false);
    }

    /**
     * @group dav
     */
    public function test_dav_limit_users_unauthorized()
    {
        $user = $this->signin();

        config(['laravelsabre.users' => 'unauthorized']);

        $response = $this->call('PROPFIND', '/dav');

        $response->assertStatus(403);
    }

    /**
     * @group dav
     */
    public function test_dav_limit_users_authorized()
    {
        $user = $this->signin();

        config(['laravelsabre.users' => $user->email]);

        $response = $this->call('PROPFIND', '/dav');

        $response->assertStatus(207);
    }
}
