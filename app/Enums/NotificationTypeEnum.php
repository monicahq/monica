<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Notification;

/**
 * What a notification is about.
 *
 * It is deliberately not limited to reminders. Several things in the product
 * need to tell somebody something, and they should all arrive in one place
 * rather than through three mechanisms: a marketplace author publishing a new
 * version of an installed structure, and a webhook subscription switched off
 * after repeated failures, are both notifications too. Only the two cases with a
 * producer are here, for the same reason {@see ReminderSourceEnum} carries only
 * the source that has a screen.
 *
 * `Digest` is the summary itself rather than one of the things summarised. The
 * folded notifications keep their own rows and their own types, and point at the
 * digest, so the history stays complete.
 *
 * The backing value is the `type` column of {@see Notification}, and it is what
 * a per type exception on the preferences is keyed by.
 */
enum NotificationTypeEnum: string
{
    case ReminderDue = 'reminder_due';
    case Digest = 'digest';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
