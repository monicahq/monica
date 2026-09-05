<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannelEnum: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Push = 'push';

    public function preferenceColumn(): string
    {
        return match ($this) {
            self::InApp => 'in_app_is_enabled',
            self::Email => 'email_is_enabled',
            self::Push => 'push_is_enabled',
        };
    }

    public function isDeliverable(): bool
    {
        return $this !== self::Push;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
