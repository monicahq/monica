<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Helpers\ReminderTime;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Override;

/**
 * The ready answers to "not now, remind me later".
 *
 * Putting a deadline off wants one click, not a date picker, so the list offers
 * the four answers people actually give. The exact date and time stays available
 * beside them for the case none of these fits.
 *
 * Every instant is worked out in the zone frozen on the rule, never the reader's.
 * A rule made in Paris fires at nine in Paris, and somebody putting it off from
 * Toronto is moving a Paris deadline: resolving "tomorrow" against their own
 * clock would move it to a different hour than the one the rule promises, and the
 * screen would then describe an instant the engine does not hold.
 *
 * Only the hourly answer keeps the clock where it is. "In an hour" means an hour
 * from now whatever the time is; the other three mean the rule's own hour on a
 * later day, which is the hour somebody reads a reminder about a person rather
 * than whatever minute they happened to press the button.
 */
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

    /**
     * The instant this answer moves the deadline to, in UTC, ready for the
     * action that only takes an absolute instant.
     */
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The column reads back as `H:i:s`, and a rule whose time nothing can parse
     * would otherwise silently land the deadline at midnight.
     */
    private function atTheRulesHour(CarbonImmutable $day, Reminder $reminder): CarbonImmutable
    {
        $time = (string) $reminder->time_of_day;

        if (! ReminderTime::isATime($time)) {
            $time = Reminder::DEFAULT_TIME;
        }

        return $day->setTimeFromTimeString($time);
    }

    /**
     * The name of this answer, which the dialog offering the choice reads.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
