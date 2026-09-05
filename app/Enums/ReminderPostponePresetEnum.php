<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Helpers\ReminderTime;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Override;

enum ReminderPostponePresetEnum: string implements Translatable
{
    case InAnHour = 'in_an_hour';
    case Tomorrow = 'tomorrow';
    case InAWeek = 'in_a_week';
    case InAMonth = 'in_a_month';

    public function label(): string
    {
        return match ($this) {
            self::InAnHour => 'In an hour',
            self::Tomorrow => 'Tomorrow',
            self::InAWeek => 'In a week',
            self::InAMonth => 'In a month',
        };
    }

    public function resolve(Reminder $reminder, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $local = ($from ?? CarbonImmutable::now())->setTimezone($reminder->timezone);

        $moved = match ($this) {
            self::InAnHour => $local->addHour(),
            self::Tomorrow => $this->atTheRulesHour($local->addDay(), $reminder),
            self::InAWeek => $this->atTheRulesHour($local->addWeek(), $reminder),
            self::InAMonth => $this->atTheRulesHour($local->addMonth(), $reminder),
        };

        return $moved->setTimezone('UTC');
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    private function atTheRulesHour(CarbonImmutable $day, Reminder $reminder): CarbonImmutable
    {
        $time = (string) $reminder->time_of_day;

        if (! ReminderTime::isATime($time)) {
            $time = Reminder::DEFAULT_TIME;
        }

        return $day->setTimeFromTimeString($time);
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
