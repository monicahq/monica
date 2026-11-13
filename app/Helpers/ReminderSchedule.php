<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Models\Reminder;
use Carbon\CarbonImmutable;

/**
 * Every candidate is computed from the anchor in one step rather than by stepping repeatedly, so a
 * rule anchored on 29 February does not drift to the 28th, and every step is taken without overflow,
 * so a rule anchored on the 31st lands on the last day of a shorter month.
 */
class ReminderSchedule
{
    private const int MAXIMUM_CYCLES = 1000;

    public static function nextDate(Reminder $reminder, ?CarbonImmutable $from = null): ?CarbonImmutable
    {
        $anchor = self::anchor($reminder, $from);

        if ($anchor === null) {
            return null;
        }

        $from ??= self::today($reminder);

        if (! $reminder->cadence->recurs()) {
            return $anchor->lessThan($from) ? null : $anchor;
        }

        return self::step($anchor, $from, $reminder->cadence->value, max(1, $reminder->frequency_interval));
    }

    /**
     * Every deadline the rule produces between two instants: one per cycle, on the cycle's own day.
     *
     * @return list<array{local_date: CarbonImmutable, scheduled_for: CarbonImmutable}>
     */
    public static function occurrencesBetween(Reminder $reminder, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if ($to->lessThan($from)) {
            return [];
        }

        $anchor = self::anchor($reminder, $from->setTimezone($reminder->timezone)->startOfDay());

        if ($anchor === null) {
            return [];
        }

        $scanStart = $from->setTimezone($reminder->timezone)->startOfDay();
        $scanEnd = $to->setTimezone($reminder->timezone)->startOfDay();

        $occurrences = [];

        foreach (self::cycles($reminder, $anchor, $scanStart, $scanEnd) as $cycle) {
            $instant = self::instantOf($reminder, $cycle);

            if ($instant->lessThan($from) || $instant->greaterThan($to)) {
                continue;
            }

            $occurrences[] = [
                'local_date' => $cycle,
                'scheduled_for' => $instant,
            ];
        }

        return $occurrences;
    }

    public static function hasADateLeft(Reminder $reminder): bool
    {
        if ($reminder->cadence->recurs()) {
            return true;
        }

        $date = self::nextDate($reminder);

        if ($date === null) {
            return false;
        }

        return self::instantOf($reminder, $date)->greaterThan(CarbonImmutable::now());
    }

    public static function instantOf(Reminder $reminder, CarbonImmutable $localDate): CarbonImmutable
    {
        $time = mb_substr($reminder->time_of_day, 0, 5);

        return CarbonImmutable::parse("{$localDate->format('Y-m-d')} {$time}", $reminder->timezone)->utc();
    }

    /** @return list<CarbonImmutable> */
    private static function cycles(Reminder $reminder, CarbonImmutable $anchor, CarbonImmutable $scanStart, CarbonImmutable $scanEnd): array
    {
        if (! $reminder->cadence->recurs()) {
            return $anchor->betweenIncluded($scanStart, $scanEnd) ? [$anchor] : [];
        }

        $cadence = $reminder->cadence->value;
        $interval = max(1, $reminder->frequency_interval);
        $step = self::stepsUntil($anchor, $scanStart, $cadence, $interval);
        $cycles = [];

        while (count($cycles) < self::MAXIMUM_CYCLES) {
            $candidate = self::add($anchor, $cadence, $step * $interval);

            if ($candidate->greaterThan($scanEnd)) {
                break;
            }

            if (! $candidate->lessThan($scanStart)) {
                $cycles[] = $candidate;
            }

            $step++;
        }

        return $cycles;
    }

    private static function anchor(Reminder $reminder, ?CarbonImmutable $from): ?CarbonImmutable
    {
        if ($reminder->source === ReminderSourceEnum::Manual) {
            return $reminder->starts_on === null
                ? null
                : CarbonImmutable::parse($reminder->starts_on->format('Y-m-d'), $reminder->timezone)->startOfDay();
        }

        $value = $reminder->fieldValue;

        if ($value?->value_date === null) {
            return null;
        }

        $date = CarbonImmutable::parse($value->value_date->format('Y-m-d'), $reminder->timezone)->startOfDay();

        if (! $value->year_is_unknown) {
            return $date;
        }

        if ($reminder->cadence === ReminderCadenceEnum::Yearly) {
            return self::inAYearHoldingTheDay($date, ($from ?? self::today($reminder))->year);
        }

        $reference = self::scheduleChangedOn($reminder);

        if ($reminder->cadence->recurs()) {
            return self::inAYearHoldingTheDay($date, $reference->year);
        }

        $once = self::onDayOf($date, $reference->year);

        return $once->lessThan($reference) ? self::onDayOf($date, $reference->year + 1) : $once;
    }

    private static function inAYearHoldingTheDay(CarbonImmutable $date, int $year): CarbonImmutable
    {
        while (! checkdate($date->month, $date->day, $year)) {
            $year--;
        }

        return $date->setDate($year, $date->month, $date->day);
    }

    private static function onDayOf(CarbonImmutable $date, int $year): CarbonImmutable
    {
        return $date->setDate($year, $date->month, min($date->day, CarbonImmutable::create($year, $date->month, 1)->daysInMonth));
    }

    private static function scheduleChangedOn(Reminder $reminder): CarbonImmutable
    {
        $changedAt = $reminder->schedule_changed_at ?? $reminder->created_at;

        if ($changedAt === null) {
            return self::today($reminder);
        }

        return $changedAt->setTimezone($reminder->timezone)->startOfDay();
    }

    private static function today(Reminder $reminder): CarbonImmutable
    {
        return CarbonImmutable::now($reminder->timezone)->startOfDay();
    }

    private static function step(CarbonImmutable $anchor, CarbonImmutable $from, string $cadence, int $interval): CarbonImmutable
    {
        return self::add($anchor, $cadence, self::stepsUntil($anchor, $from, $cadence, $interval) * $interval);
    }

    private static function stepsUntil(CarbonImmutable $anchor, CarbonImmutable $from, string $cadence, int $interval): int
    {
        $elapsed = match ($cadence) {
            'weekly' => (int) floor($anchor->diffInWeeks($from)),
            'monthly' => (int) floor($anchor->diffInMonths($from)),
            default => (int) floor($anchor->diffInYears($from)),
        };

        $steps = max(0, intdiv($elapsed, $interval));

        return self::add($anchor, $cadence, $steps * $interval)->lessThan($from)
            ? $steps + 1
            : $steps;
    }

    private static function add(CarbonImmutable $anchor, string $cadence, int $count): CarbonImmutable
    {
        return match ($cadence) {
            'weekly' => $anchor->addWeeks($count),
            'monthly' => $anchor->addMonthsNoOverflow($count),
            default => $anchor->addYearsNoOverflow($count),
        };
    }
}
