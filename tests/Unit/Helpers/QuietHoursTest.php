<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\QuietHours;
use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuietHoursTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_nothing_when_nobody_asked_for_quiet(): void
    {
        $preference = NotificationPreference::factory()->create();
        $instant = $this->at('2027-05-12 01:00');

        $this->assertFalse(QuietHours::hold($preference, $instant));
        $this->assertTrue(QuietHours::release($preference, $instant)->equalTo($instant));
    }

    #[Test]
    public function it_holds_the_small_hours_of_a_window_that_crosses_midnight(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('22:00', '08:00')->create();

        $this->assertTrue(QuietHours::hold($preference, $this->at('2027-05-12 01:00')));
        $this->assertTrue(QuietHours::hold($preference, $this->at('2027-05-11 23:30')));
        $this->assertFalse(QuietHours::hold($preference, $this->at('2027-05-11 21:00')));
    }

    #[Test]
    public function it_releases_a_notification_due_at_three_in_the_morning_at_eight(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('22:00', '08:00')->create();

        $released = QuietHours::release($preference, $this->at('2027-05-12 03:00'));

        $this->assertSame('2027-05-12 08:00', $this->local($released));
    }

    #[Test]
    public function it_releases_a_notification_due_late_at_night_the_next_morning(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('22:00', '08:00')->create();

        $released = QuietHours::release($preference, $this->at('2027-05-11 23:30'));

        $this->assertSame('2027-05-12 08:00', $this->local($released));
    }

    #[Test]
    public function it_reads_a_window_that_does_not_cross_midnight_the_plain_way(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('09:00', '17:00')->create();

        $this->assertTrue(QuietHours::hold($preference, $this->at('2027-05-12 12:00')));
        $this->assertFalse(QuietHours::hold($preference, $this->at('2027-05-12 08:00')));
        $this->assertSame('2027-05-12 17:00', $this->local(QuietHours::release($preference, $this->at('2027-05-12 12:00'))));
    }

    #[Test]
    public function it_lets_the_first_minute_after_the_window_through(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('22:00', '08:00')->create();

        $this->assertFalse(QuietHours::hold($preference, $this->at('2027-05-12 08:00')));
    }

    #[Test]
    public function it_holds_the_first_minute_of_the_window(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('22:00', '08:00')->create();

        $this->assertTrue(QuietHours::hold($preference, $this->at('2027-05-11 22:00')));
    }

    #[Test]
    public function it_treats_a_window_with_both_ends_the_same_as_a_whole_day_of_quiet(): void
    {
        $preference = NotificationPreference::factory()->quietBetween('09:00', '09:00')->create();

        $this->assertTrue(QuietHours::hold($preference, $this->at('2027-05-12 12:00')));
        $this->assertSame('2027-05-13 09:00', $this->local(QuietHours::release($preference, $this->at('2027-05-12 12:00'))));
    }

    #[Test]
    public function it_reads_the_window_in_the_zone_the_account_chose(): void
    {
        $preference = NotificationPreference::factory()
            ->quietBetween('22:00', '08:00')
            ->create(['timezone' => 'America/New_York']);

        $this->assertFalse(QuietHours::hold($preference, CarbonImmutable::parse('2027-05-12 16:00', 'UTC')));
        $this->assertTrue(QuietHours::hold($preference, CarbonImmutable::parse('2027-05-12 07:00', 'UTC')));
        $this->assertTrue(QuietHours::hold($preference, CarbonImmutable::parse('2027-05-12 03:00', 'UTC')));
    }

    #[Test]
    public function it_holds_nothing_when_the_window_has_only_one_end(): void
    {
        $preference = NotificationPreference::factory()->create([
            'quiet_hours_are_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => null,
        ]);

        $this->assertFalse(QuietHours::hold($preference, $this->at('2027-05-12 23:00')));
    }

    private function at(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, 'Europe/Paris')->utc();
    }

    private function local(CarbonImmutable $instant): string
    {
        return $instant->setTimezone('Europe/Paris')->format('Y-m-d H:i');
    }
}
