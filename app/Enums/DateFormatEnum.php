<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Carbon\CarbonInterface;

enum DateFormatEnum: string implements Translatable
{
    case DayFirst = 'd/m/Y';
    case MonthFirst = 'm/d/Y';
    case YearFirst = 'Y-m-d';
    case MonthSpelled = 'j F Y';

    /**
     * What the format is called on the screen where somebody chooses it. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::DayFirst => 'Day first',
            self::MonthFirst => 'Month first',
            self::YearFirst => 'Year first',
            self::MonthSpelled => 'Month spelled out',
        };
    }

    /**
     * The same day written this way, shown beside the name so nobody has to work
     * out which order they mean. It is the twenty first of the month on purpose,
     * since a day the month could also be would read the same in two of these.
     */
    public function example(): string
    {
        return $this->format(now()->setDay(21));
    }

    public function format(CarbonInterface $date): string
    {
        return self::write($date, $this->value);
    }

    /**
     * A date written in a format nobody chose, which is the one the interface
     * language reads dates in. The month is spelled in that language too, so
     * the formatting has to go through the translated formatter either way.
     */
    public static function write(CarbonInterface $date, string $format): string
    {
        return $date->locale(app()->getLocale())->translatedFormat($format);
    }

    /**
     * The name of this case, which the screen that offers the choice reads.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
