<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderOccurrenceStatusEnum: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Postponed = 'postponed';
    case Dismissed = 'dismissed';
    case Cancelled = 'cancelled';

    public function isActionable(): bool
    {
        return in_array($this, [self::Pending, self::Notified, self::Postponed], true);
    }

    public function isTerminal(): bool
    {
        return ! $this->isActionable();
    }

    public function awaitsDelivery(): bool
    {
        return in_array($this, [self::Pending, self::Postponed], true);
    }

    /** @return list<string> */
    public static function awaitingDelivery(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->awaitsDelivery()),
        ));
    }

    /** @return list<string> */
    public static function actionable(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->isActionable()),
        ));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
