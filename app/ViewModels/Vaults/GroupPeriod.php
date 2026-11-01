<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DatePrecisionEnum;
use App\Helpers\AvailableLocales;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Writes the dates of a group or of a membership as precisely as they are known, for every screen
 * that shows one.
 */
class GroupPeriod
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function date(?CarbonImmutable $date, DatePrecisionEnum $precision): ?string
    {
        if (! $date instanceof CarbonImmutable) {
            return null;
        }

        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return match ($precision) {
            DatePrecisionEnum::Day, DatePrecisionEnum::Unknown => DateFormatEnum::write($date, $format),
            DatePrecisionEnum::Month => DateFormatEnum::write($date, 'F Y'),
            DatePrecisionEnum::Year => (string) $date->year,
            DatePrecisionEnum::Decade => sprintf('%d–%d', intdiv($date->year, 10) * 10, intdiv($date->year, 10) * 10 + 9),
        };
    }

    /**
     * The wordings for a day carry an article in several languages, which a month, a year or a
     * decade does not take, so a date known less precisely is worded on its own.
     */
    public function between(?CarbonImmutable $start, DatePrecisionEnum $startPrecision, ?CarbonImmutable $end, DatePrecisionEnum $endPrecision): ?string
    {
        $from = $this->date($start, $startPrecision);
        $until = $this->date($end, $endPrecision);
        $startsOnADay = in_array($startPrecision, [DatePrecisionEnum::Day, DatePrecisionEnum::Unknown], true);
        $endsOnADay = in_array($endPrecision, [DatePrecisionEnum::Day, DatePrecisionEnum::Unknown], true);

        if ($from === null && $until === null) {
            return null;
        }

        if ($until === null) {
            return $startsOnADay ? __('Since :start', ['start' => $from]) : __('Since :period', ['period' => $from]);
        }

        if ($from === null) {
            return $endsOnADay ? __('Until :end', ['end' => $until]) : __('Until :period', ['period' => $until]);
        }

        if ($startsOnADay && $endsOnADay) {
            return __(':start to :end', ['start' => $from, 'end' => $until]);
        }

        return __('Between :start and :end', ['start' => $from, 'end' => $until]);
    }
}
