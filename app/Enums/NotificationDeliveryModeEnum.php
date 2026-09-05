<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationDeliveryModeEnum: string
{
    case Immediate = 'immediate';
    case Digest = 'digest';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
