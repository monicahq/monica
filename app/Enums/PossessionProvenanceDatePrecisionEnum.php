<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Override;

enum PossessionProvenanceDatePrecisionEnum: string implements Translatable
{
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';
    case Circa = 'circa';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Exact date',
            self::Month => 'Month and year',
            self::Year => 'Year',
            self::Circa => 'Around a year',
            self::Unknown => 'Unknown',
        };
    }

    public function help(): string
    {
        return match ($this) {
            self::Day => 'The full day is known.',
            self::Month => 'The month is known, not the day.',
            self::Year => 'Only the year is known.',
            self::Circa => 'An approximate year, shown as “about”.',
            self::Unknown => 'The event is kept at the end of the chain.',
        };
    }

    /**
     * Read a date written in the shape this precision gives: 1962-03-14, 1962-03 or 1962, a year before
     * 1000 written with or without its leading zeros, since a number field drops them. A month is
     * kept as its first day and a year as the first of January. Returns null for an unknown date, and
     * false when the text does not match the precision.
     */
    public function parse(?string $written): CarbonImmutable|false|null
    {
        $written = $written === null ? '' : mb_trim($written);

        if ($this === self::Unknown) {
            return null;
        }

        if ($written === '') {
            return null;
        }

        $pattern = match ($this) {
            self::Day => '/^\d{4}-\d{2}-\d{2}$/',
            self::Month => '/^\d{1,4}-\d{2}$/',
            self::Year, self::Circa => '/^\d{1,4}$/',
        };

        if (preg_match($pattern, $written) !== 1) {
            return false;
        }

        $parts = array_map(intval(...), explode('-', $written));
        $year = $parts[0];
        $month = $parts[1] ?? 1;
        $day = $parts[2] ?? 1;

        if ($year < 1 || ! checkdate($month, $day, $year)) {
            return false;
        }

        return CarbonImmutable::create($year, $month, $day)?->startOfDay() ?? false;
    }

    /**
     * Write a date back in the shape this precision gives.
     */
    public function format(?CarbonImmutable $date): ?string
    {
        if ($date === null || $this === self::Unknown) {
            return null;
        }

        return match ($this) {
            self::Day => $date->format('Y-m-d'),
            self::Month => $date->format('Y-m'),
            self::Year, self::Circa => $date->format('Y'),
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->help()];
    }
}
