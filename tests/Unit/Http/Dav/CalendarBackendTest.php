<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\CalendarBackend;
use App\Models\User;
use App\Models\Vault;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;
use Tests\TestCase;

class CalendarBackendTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_a_dates_calendar_and_a_task_list_per_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);

        $calendars = new CalendarBackend($user)->getCalendarsForUser('principals/'.$user->email);

        $this->assertCount(2, $calendars);
        $this->assertEquals($vault->uuid.'-dates', $calendars[0]['uri']);
        $this->assertEquals($vault->uuid.'-reminders', $calendars[1]['uri']);
        $this->assertStringContainsString('Scranton', $calendars[0]['{DAV:}displayname']);
    }

    #[Test]
    public function it_offers_nothing_of_another_account(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => User::factory()->create()->account_id]);

        $this->assertEmpty(new CalendarBackend($user)->getCalendarsForUser('principals/'.$user->email));
    }

    #[Test]
    public function it_hides_a_calendar_of_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = Vault::factory()->create(['account_id' => User::factory()->create()->account_id]);

        $this->expectException(NotFound::class);

        new CalendarBackend($user)->getCalendarObjects($theirs->id.':dates');
    }

    #[Test]
    public function it_writes_nothing_a_device_asks_of_a_calendar(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        $propPatch = new PropPatch(['{DAV:}displayname' => 'Utica']);

        new CalendarBackend($user)->updateCalendar($vault->id.':dates', $propPatch);

        $this->assertFalse($propPatch->commit());
        $this->assertEquals('Scranton', $vault->refresh()->name);
    }

    #[Test]
    public function it_answers_that_an_entry_it_does_not_hold_is_not_there(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertNull(new CalendarBackend($user)->getCalendarObject($vault->id.':dates', 'nothing.ics'));
    }

    #[Test]
    #[DataProvider('writes')]
    public function it_refuses_every_write_a_device_asks_of_a_calendar(Closure $write): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(Forbidden::class);

        $write(new CalendarBackend($user), $user, "{$vault->id}:dates");
    }

    /** @return array<string, array{Closure}> */
    public static function writes(): array
    {
        return [
            'make a calendar' => [fn (CalendarBackend $backend, User $user, string $calendarId) => $backend->createCalendar("principals/{$user->email}", 'invented', [])],
            'delete a calendar' => [fn (CalendarBackend $backend, User $user, string $calendarId) => $backend->deleteCalendar($calendarId)],
            'write an entry' => [fn (CalendarBackend $backend, User $user, string $calendarId) => $backend->createCalendarObject($calendarId, 'invented.ics', 'BEGIN:VCALENDAR')],
            'change an entry' => [fn (CalendarBackend $backend, User $user, string $calendarId) => $backend->updateCalendarObject($calendarId, 'invented.ics', 'BEGIN:VCALENDAR')],
            'remove an entry' => [fn (CalendarBackend $backend, User $user, string $calendarId) => $backend->deleteCalendarObject($calendarId, 'invented.ics')],
        ];
    }
}
