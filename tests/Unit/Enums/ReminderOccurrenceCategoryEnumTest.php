<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceCategoryEnumTest extends TestCase
{
    #[Test]
    public function it_places_a_deadline_before_its_day_among_what_is_upcoming(): void
    {
        $this->assertSame(ReminderOccurrenceCategoryEnum::Upcoming, $this->categoryOf(day: '2026-10-14', today: '2026-10-13 23:59'));
    }

    #[Test]
    public function it_places_a_deadline_on_its_day_among_today_at_any_hour(): void
    {
        $this->assertSame(ReminderOccurrenceCategoryEnum::Today, $this->categoryOf(day: '2026-10-14', today: '2026-10-14 00:00'));
        $this->assertSame(ReminderOccurrenceCategoryEnum::Today, $this->categoryOf(day: '2026-10-14', today: '2026-10-14 23:59'));
    }

    #[Test]
    public function it_places_a_deadline_from_the_day_after_among_what_is_overdue(): void
    {
        $this->assertSame(ReminderOccurrenceCategoryEnum::Overdue, $this->categoryOf(day: '2026-10-14', today: '2026-10-15 00:00'));
        $this->assertSame(ReminderOccurrenceCategoryEnum::Overdue, $this->categoryOf(day: '2026-10-14', today: '2027-03-01 12:00'));
    }

    private function categoryOf(string $day, string $today): ReminderOccurrenceCategoryEnum
    {
        $occurrence = new ReminderOccurrence(['local_date' => $day]);

        return ReminderOccurrenceCategoryEnum::for($occurrence, CarbonImmutable::parse($today));
    }
}
