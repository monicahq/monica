<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ReminderCadenceEnum: string implements Translatable
{
    case Once = 'once';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'once and no more',
            self::Weekly => 'every week',
            self::Monthly => 'every month',
            self::Yearly => 'every year',
        };
    }

    public function recurs(): bool
    {
        return $this !== self::Once;
    }

    /** @return list<self> */
    public static function recurring(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case): bool => $case->recurs()));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
