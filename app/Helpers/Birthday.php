<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\FieldValue;
use Carbon\CarbonImmutable;

class Birthday
{
    public function __construct(
        private readonly FieldValue $value,
        private readonly CarbonImmutable $today,
    ) {}

    public function next(): ?CarbonImmutable
    {
        if ($this->value->month_is_unknown || $this->value->value_date === null) {
            return null;
        }

        return $this->nextAnniversary($this->value->value_date);
    }

    public function daysUntilNext(): ?int
    {
        $next = $this->next();

        if ($next === null) {
            return null;
        }

        return (int) $this->today->diffInDays($next);
    }

    public function isUpcoming(): bool
    {
        $days = $this->daysUntilNext();

        return $days !== null && $days <= ReminderOccurrenceCategoryEnum::UpcomingDays;
    }

    public function age(): ?int
    {
        $date = $this->value->value_date;

        if ($this->value->year_is_unknown || $this->value->month_is_unknown || $date === null) {
            return null;
        }

        if ($date->toDateString() > $this->today->toDateString()) {
            return null;
        }

        return $this->today->year - $date->year - ($this->inYear($date, $this->today->year)->toDateString() > $this->today->toDateString() ? 1 : 0);
    }

    private function nextAnniversary(CarbonImmutable $birthday): CarbonImmutable
    {
        $thisYear = $this->inYear($birthday, $this->today->year);

        return $thisYear->toDateString() < $this->today->toDateString()
            ? $this->inYear($birthday, $this->today->year + 1)
            : $thisYear;
    }

    private function inYear(CarbonImmutable $birthday, int $year): CarbonImmutable
    {
        $daysInMonth = CarbonImmutable::create($year, $birthday->month, 1, 0, 0, 0, $this->today->timezone)->daysInMonth;

        return CarbonImmutable::create($year, $birthday->month, min($birthday->day, $daysInMonth), 0, 0, 0, $this->today->timezone);
    }
}
