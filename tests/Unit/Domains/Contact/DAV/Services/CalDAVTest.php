<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class CalDAVTest extends TestCase
{
    use CardEtag, DatabaseTransactions;

    #[Test]
    #[Group('dav')]
    public function test_caldav_propfind_calendars()
    {
        $user = $this->createUser();

        $response = $this->call('PROPFIND', '/dav/calendars');

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee('<d:response><d:href>/dav/calendars/</d:href>', false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/</d:href>", false);
    }

    #[Test]
    #[Group('dav')]
    public function test_caldav_propfind_calendars_user()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $vaultname = rawurlencode($vault->name);

        $response = $this->call('PROPFIND', "/dav/calendars/{$user->email}");

        $response->assertStatus(207);
        $response->assertHeader('X-Sabre-Version');

        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/dates-$vaultname/</d:href>", false);
        $response->assertSee("<d:response><d:href>/dav/calendars/{$user->email}/tasks-$vaultname/</d:href>", false);
    }
}
