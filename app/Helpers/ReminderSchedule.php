<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Models\Reminder;
use Carbon\CarbonImmutable;

/**
 * When a rule falls next, and when each of its moments does.
 *
 * It lives here rather than on the model or in a view model because two very
 * different callers need it. A screen shows the next date while somebody is
 * setting the rule up, and the job that generates concrete deadlines will read
 * exactly the same arithmetic once {@see Reminder} grows
 * occurrences. A view model may not be consumed outside Blade, so it cannot be
 * the home of a calculation a queued job depends on.
 *
 * Two questions, not one. A screen asks when the rule falls next, and answers it
 * from the rule alone. The generator asks something different: every deadline
 * whose instant falls inside a window, which is what {@see occurrencesBetween()}
 * enumerates, and which reaches into cycles outside the window in both
 * directions because a moment can.
 *
 * Two properties are worth stating, because getting either wrong is the classic
 * bug in date arithmetic. Every candidate is computed from the anchor in one
 * step rather than by stepping repeatedly, so a rule anchored on 29 February
 * lands on 29 February again in a leap year instead of drifting to the 28th for
 * ever. And every step is taken without overflow, so a rule anchored on the
 * 31st lands on the last day of a shorter month rather than in the next one.
 */
class ReminderSchedule
{
    /**
     * The most cycles one window is expanded into. A weekly rule over the
     * longest sensible window is a few hundred, so this is a stop against a
     * rule nobody meant rather than a limit anybody reaches.
     */
    private const int MAXIMUM_CYCLES = 1000;

    /**
     * The next day the rule falls on, in its own frozen zone, or null when it
     * has nothing ahead of it: a rule that fires once and whose day has gone,
     * or a rule whose value has been cleared.
     */
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
     * Every moment of the rule, each with the day it falls on, in the order
     * somebody put them in. A rule with nothing ahead of it has moments with no
     * date, since the moments are still what it says and only the day is
     * unknown.
     *
     * @return list<array{direction: ReminderMomentDirectionEnum, amount: int|null, unit: ReminderMomentUnitEnum|null, date: CarbonImmutable|null}>
     */
    public static function momentDates(Reminder $reminder, ?CarbonImmutable $from = null): array
    {
        $next = self::nextDate($reminder, $from);
        $moments = [];

        foreach ($reminder->moments as $moment) {
            $direction = ReminderMomentDirectionEnum::tryFrom((string) ($moment['direction'] ?? ''));

            if ($direction === null) {
                continue;
            }

            $unit = $direction->needsAnAmount()
                ? ReminderMomentUnitEnum::tryFrom((string) ($moment['unit'] ?? ''))
                : null;

            $amount = $direction->needsAnAmount() ? (int) ($moment['amount'] ?? 0) : null;

            $moments[] = [
                'direction' => $direction,
                'amount' => $amount,
                'unit' => $unit,
                'date' => $next === null ? null : self::shift($next, $direction, $amount, $unit),
            ];
        }

        return $moments;
    }

    /**
     * Every deadline the rule produces whose instant falls inside the window,
     * in the order the cycles come and, within a cycle, the order somebody put
     * the moments in.
     *
     * A moment reaches outside the window in both directions, so the cycles
     * expanded run from the start of the window minus the furthest moment after
     * a date to the end of it plus the furthest moment before one. Both reaches
     * are bounded generously, a month counted as thirty-one days, because a
     * slightly wide scan costs a few candidates that are thrown away and a
     * narrow one loses deadlines outright.
     *
     * The instant is where the zone frozen on the rule finally matters. A local
     * time that does not exist, because the clocks went forward through it, is
     * moved past the gap; one that happens twice, because they went back,
     * resolves to the later of its two instants. Both are what PHP does with a
     * local time in a zone, and both are pinned by a test rather than
     * reimplemented here.
     *
     * @return list<array{source_local_date: CarbonImmutable, direction: ReminderMomentDirectionEnum, amount: int|null, unit: ReminderMomentUnitEnum|null, local_date: CarbonImmutable, scheduled_for: CarbonImmutable}>
     */
    public static function occurrencesBetween(Reminder $reminder, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if ($to->lessThan($from)) {
            return [];
        }

        $moments = self::normalisedMoments($reminder);

        if ($moments === []) {
            return [];
        }

        $anchor = self::anchor($reminder, $from->setTimezone($reminder->timezone)->startOfDay());

        if ($anchor === null) {
            return [];
        }

        $reach = self::reachOf($moments);
        $scanStart = $from->setTimezone($reminder->timezone)->startOfDay()->subDays($reach['after']);
        $scanEnd = $to->setTimezone($reminder->timezone)->startOfDay()->addDays($reach['before']);

        $occurrences = [];

        foreach (self::cycles($reminder, $anchor, $scanStart, $scanEnd) as $cycle) {
            foreach ($moments as $moment) {
                $localDate = self::shift($cycle, $moment['direction'], $moment['amount'], $moment['unit']);
                $instant = self::instantOf($reminder, $localDate);

                if ($instant->lessThan($from) || $instant->greaterThan($to)) {
                    continue;
                }

                $occurrences[] = [
                    'source_local_date' => $cycle,
                    'direction' => $moment['direction'],
                    'amount' => $moment['amount'],
                    'unit' => $moment['unit'],
                    'local_date' => $localDate,
                    'scheduled_for' => $instant,
                ];
            }
        }

        return $occurrences;
    }

    /**
     * The instant a local day of this rule falls at, in UTC.
     */
    public static function instantOf(Reminder $reminder, CarbonImmutable $localDate): CarbonImmutable
    {
        $time = mb_substr($reminder->time_of_day, 0, 5);

        return CarbonImmutable::parse("{$localDate->format('Y-m-d')} {$time}", $reminder->timezone)->utc();
    }

    /**
     * The rule's moments with their direction and unit read, and anything
     * unreadable dropped. It is what both the screen and the generator expand.
     *
     * @return list<array{direction: ReminderMomentDirectionEnum, amount: int|null, unit: ReminderMomentUnitEnum|null}>
     */
    private static function normalisedMoments(Reminder $reminder): array
    {
        $moments = [];

        foreach ($reminder->moments as $moment) {
            $direction = ReminderMomentDirectionEnum::tryFrom((string) ($moment['direction'] ?? ''));

            if ($direction === null) {
                continue;
            }

            $carries = $direction->needsAnAmount();

            $moments[] = [
                'direction' => $direction,
                'amount' => $carries ? (int) ($moment['amount'] ?? 0) : null,
                'unit' => $carries ? ReminderMomentUnitEnum::tryFrom((string) ($moment['unit'] ?? '')) : null,
            ];
        }

        return $moments;
    }

    /**
     * How many days the moments reach either side of the date, generously.
     *
     * @param  list<array{direction: ReminderMomentDirectionEnum, amount: int|null, unit: ReminderMomentUnitEnum|null}>  $moments
     * @return array{before: int, after: int}
     */
    private static function reachOf(array $moments): array
    {
        $reach = ['before' => 0, 'after' => 0];

        foreach ($moments as $moment) {
            if ($moment['unit'] === null || $moment['amount'] === null) {
                continue;
            }

            $days = $moment['amount'] * match ($moment['unit']) {
                ReminderMomentUnitEnum::Days => 1,
                ReminderMomentUnitEnum::Weeks => 7,
                ReminderMomentUnitEnum::Months => 31,
            };

            $side = $moment['direction'] === ReminderMomentDirectionEnum::Before ? 'before' : 'after';
            $reach[$side] = max($reach[$side], $days);
        }

        return $reach;
    }

    /**
     * Every local date the cadence falls on between the two days given, counted
     * from the anchor in one step each time so a rule anchored on 29 February
     * does not drift.
     *
     * @return list<CarbonImmutable>
     */
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

    /**
     * The day the rule reads, moved onto a year it can actually be counted
     * from.
     *
     * A date whose year nobody knows is stored under a placeholder year, and
     * counting a rhythm of three years from 1900 would be counting from a year
     * nobody entered. The day and the month are what somebody typed, so the
     * anchor takes those and the current year.
     */
    private static function anchor(Reminder $reminder, ?CarbonImmutable $from): ?CarbonImmutable
    {
        $value = $reminder->fieldValue;

        if ($value?->value_date === null) {
            return null;
        }

        $date = CarbonImmutable::parse($value->value_date->format('Y-m-d'), $reminder->timezone)->startOfDay();

        if (! $value->year_is_unknown) {
            return $date;
        }

        $year = ($from ?? self::today($reminder))->year;

        return $date->setDate($year, $date->month, min($date->day, CarbonImmutable::create($year, $date->month, 1)->daysInMonth));
    }

    /**
     * Today where the rule thinks it lives, which is the zone frozen on it and
     * never the reader's current one.
     */
    private static function today(Reminder $reminder): CarbonImmutable
    {
        return CarbonImmutable::now($reminder->timezone)->startOfDay();
    }

    /**
     * The first candidate on or after the day given, counted from the anchor in
     * one step.
     */
    private static function step(CarbonImmutable $anchor, CarbonImmutable $from, string $cadence, int $interval): CarbonImmutable
    {
        return self::add($anchor, $cadence, self::stepsUntil($anchor, $from, $cadence, $interval) * $interval);
    }

    /**
     * How many whole cadences from the anchor land on or after the day given.
     */
    private static function stepsUntil(CarbonImmutable $anchor, CarbonImmutable $from, string $cadence, int $interval): int
    {
        $elapsed = match ($cadence) {
            'weekly' => (int) floor($anchor->diffInWeeks($from)),
            'monthly' => (int) floor($anchor->diffInMonths($from)),
            default => (int) floor($anchor->diffInYears($from)),
        };

        $steps = max(0, intdiv($elapsed, $interval));

        /* One extra step is taken rather than predicted. Whether the candidate
           has already gone depends on the day the anchor lands on after
           clamping, which is only known once the step has been made. */
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

    /**
     * One moment's day, counted from the day the rule falls on.
     */
    private static function shift(CarbonImmutable $date, ReminderMomentDirectionEnum $direction, ?int $amount, ?ReminderMomentUnitEnum $unit): CarbonImmutable
    {
        if (! $direction->needsAnAmount() || $amount === null || $unit === null) {
            return $date;
        }

        $signed = $direction === ReminderMomentDirectionEnum::Before ? -$amount : $amount;

        return match ($unit) {
            ReminderMomentUnitEnum::Days => $date->addDays($signed),
            ReminderMomentUnitEnum::Weeks => $date->addWeeks($signed),
            ReminderMomentUnitEnum::Months => $date->addMonthsNoOverflow($signed),
        };
    }
}
