<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Carbon\CarbonInterface;
use Override;

enum DateFormatEnum: string implements Translatable
{
    case DayFirst = 'd/m/Y';
    case MonthFirst = 'm/d/Y';
    case YearFirst = 'Y-m-d';
    case MonthSpelled = 'j F Y';

    public function label(): string
    {
        return match ($this) {
            self::DayFirst => 'Day first',
            self::MonthFirst => 'Month first',
            self::YearFirst => 'Year first',
            self::MonthSpelled => 'Month spelled out',
        };
    }

    public function example(): string
    {
        return $this->format(now()->setDay(21));
    }

    public function format(CarbonInterface $date): string
    {
        return self::write($date, $this->value);
    }

    public static function write(CarbonInterface $date, string $format): string
    {
        return $date->locale(app()->getLocale())->translatedFormat($format);
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
