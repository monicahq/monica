<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
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

    public static function instantOf(Reminder $reminder, CarbonImmutable $localDate): CarbonImmutable
    {
        $time = mb_substr($reminder->time_of_day, 0, 5);

        return CarbonImmutable::parse("{$localDate->format('Y-m-d')} {$time}", $reminder->timezone)->utc();
    }

    /**
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
