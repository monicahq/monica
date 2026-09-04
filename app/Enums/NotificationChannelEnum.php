<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Notification;
use App\Models\NotificationPreference;

/**
 * How a notification reached somebody.
 *
 * One deadline produces one notification per enabled channel, so the channel is
 * what makes two rows about the same thing different rather than duplicated.
 *
 * `Push` is a case with nothing behind it. The native applications come later
 * and push has to exist on the server side before they do, so the shape is here
 * and the preference can be switched on, while nothing claims to deliver it. A
 * case omitted now would be a migration later for a column the specification
 * already names.
 *
 * The backing value is the `channel` column of {@see Notification}, and each
 * case matches a flag on {@see NotificationPreference}.
 */
enum NotificationChannelEnum: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Push = 'push';

    /**
     * The column on the preferences saying whether this channel is on.
     */
    public function preferenceColumn(): string
    {
        return match ($this) {
            self::InApp => 'in_app_is_enabled',
            self::Email => 'email_is_enabled',
            self::Push => 'push_is_enabled',
        };
    }

    /**
     * Whether anything is actually able to deliver on this channel today. Push
     * has no driver, so a preference that switches it on still sends nothing,
     * and nothing pretends otherwise.
     */
    public function isDeliverable(): bool
    {
        return $this !== self::Push;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
