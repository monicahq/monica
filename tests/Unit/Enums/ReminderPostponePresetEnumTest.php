<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderPostponePresetEnum;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderPostponePresetEnumTest extends TestCase
{
    #[Test]
    public function it_moves_a_deadline_an_hour_on_without_touching_the_clock(): void
    {
        $reminder = $this->ruleIn(zone: 'Europe/Paris', at: '09:00:00');

        $moved = ReminderPostponePresetEnum::InAnHour->resolve(
            reminder: $reminder,
            from: CarbonImmutable::parse('2026-09-04 12:34:00', 'UTC'),
        );

        $this->assertSame('2026-09-04 13:34:00', $moved->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_moves_a_deadline_to_the_rules_own_hour_on_a_later_day(): void
    {
        $reminder = $this->ruleIn(zone: 'Europe/Paris', at: '09:00:00');
        $from = CarbonImmutable::parse('2026-09-04 12:34:00', 'UTC');

        $tomorrow = ReminderPostponePresetEnum::Tomorrow->resolve(reminder: $reminder, from: $from);
        $week = ReminderPostponePresetEnum::InAWeek->resolve(reminder: $reminder, from: $from);
        $month = ReminderPostponePresetEnum::InAMonth->resolve(reminder: $reminder, from: $from);

        $this->assertSame('2026-09-05 07:00:00', $tomorrow->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-11 07:00:00', $week->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-04 07:00:00', $month->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_works_in_the_zone_frozen_on_the_rule_and_not_the_readers(): void
    {
        $reminder = $this->ruleIn(zone: 'Europe/Paris', at: '09:00:00');

        $moved = ReminderPostponePresetEnum::Tomorrow->resolve(
            reminder: $reminder,
            from: CarbonImmutable::parse('2026-09-04 20:00:00', 'America/Toronto'),
        );

        $this->assertSame('2026-09-06 07:00:00', $moved->format('Y-m-d H:i:s'));
        $this->assertSame('09:00', $moved->setTimezone('Europe/Paris')->format('H:i'));
    }

    #[Test]
    public function it_falls_back_to_the_default_hour_when_the_rule_carries_a_time_nothing_can_parse(): void
    {
        $reminder = $this->ruleIn(zone: 'UTC', at: 'not a time');

        $moved = ReminderPostponePresetEnum::Tomorrow->resolve(
            reminder: $reminder,
            from: CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'),
        );

        $this->assertSame('2026-09-05 09:00:00', $moved->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_names_every_answer_and_offers_their_values(): void
    {
        $this->assertSame(
            ['in_an_hour', 'tomorrow', 'in_a_week', 'in_a_month'],
            ReminderPostponePresetEnum::values(),
        );

        foreach (ReminderPostponePresetEnum::cases() as $case) {
            $this->assertNotSame('', $case->label());
            $this->assertSame([$case->label()], $case->translationKeys());
        }
    }

    private function ruleIn(string $zone, string $at): Reminder
    {
        $reminder = new Reminder;
        $reminder->timezone = $zone;
        $reminder->time_of_day = $at;

        return $reminder;
    }
}
