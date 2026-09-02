<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Models\Reminder;
use Override;

/**
 * How often a rule fires.
 *
 * It is the whole of the cadence, `Once` included, rather than a boolean beside
 * a frequency. Two columns that can disagree about whether a rule repeats is one
 * column too many, and the screen asks a single question.
 *
 * `Daily` is not a case. The specification names it in section 10.1 for a manual
 * rule, and a date on a record cannot sensibly come round every day.
 *
 * A rhythm somebody sets themselves, "every three years", is this enum's
 * `Yearly` with the interval column set. It is not a case of its own, because
 * the interval is a number and not a kind of cadence.
 *
 * The backing value is the `cadence` column of {@see Reminder}.
 */
enum ReminderCadenceEnum: string implements Translatable
{
    case Once = 'once';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * What the cadence is called where somebody chooses it, written as it reads
     * after the words "Comes back".
     */
    public function label(): string
    {
        return match ($this) {
            self::Once => 'once and no more',
            self::Weekly => 'every week',
            self::Monthly => 'every month',
            self::Yearly => 'every year',
        };
    }

    /**
     * Whether the rule comes round again, which is what decides whether the
     * frequency and its interval are asked for at all.
     */
    public function recurs(): bool
    {
        return $this !== self::Once;
    }

    /**
     * The cadences somebody may pick once they have said the rule comes back.
     *
     * @return list<self>
     */
    public static function recurring(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case): bool => $case->recurs()));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
