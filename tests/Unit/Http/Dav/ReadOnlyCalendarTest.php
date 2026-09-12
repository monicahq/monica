<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\CalendarBackend;
use App\Http\Dav\ReadOnlyCalendar;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadOnlyCalendarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_grants_reading_and_nothing_else(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);
        $backend = new CalendarBackend($user);
        $calendar = new ReadOnlyCalendar($backend, $backend->getCalendarsForUser('principals/'.$user->email)[0]);

        $privileges = array_column($calendar->getACL(), 'privilege');

        $this->assertContains('{DAV:}read', $privileges);
        $this->assertNotContains('{DAV:}write', $privileges);
        $this->assertNotContains('{DAV:}write-content', $privileges);
        $this->assertNotContains('{DAV:}bind', $privileges);
        $this->assertNotContains('{DAV:}unbind', $privileges);
    }
}
