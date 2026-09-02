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
 * Nothing is stored. How far ahead deadlines are generated is a question the
 * specification has not answered, and computing on read is what keeps this
 * feature from answering it by accident.
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
        $elapsed = match ($cadence) {
            'weekly' => (int) floor($anchor->diffInWeeks($from)),
            'monthly' => (int) floor($anchor->diffInMonths($from)),
            default => (int) floor($anchor->diffInYears($from)),
        };

        $steps = max(0, intdiv($elapsed, $interval));

        /* One extra step is taken rather than predicted. Whether the candidate
           has already gone depends on the day the anchor lands on after
           clamping, which is only known once the step has been made. */
        $candidate = self::add($anchor, $cadence, $steps * $interval);

        return $candidate->lessThan($from)
            ? self::add($anchor, $cadence, ($steps + 1) * $interval)
            : $candidate;
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
