<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\NotificationPreference;

/**
 * Whether notifications arrive one at a time or folded into a summary.
 *
 * The two are a genuine choice rather than a setting nobody touches: somebody
 * who wants to be interrupted the moment a deadline falls and somebody who wants
 * one message a day are both right, and the second is what makes a busy vault
 * bearable.
 *
 * Digest mode does not drop anything. Each notification is still written, still
 * says what it says, and points at the summary that carried it.
 *
 * The backing value is the `delivery_mode` column of
 * {@see NotificationPreference}.
 */
enum NotificationDeliveryModeEnum: string
{
    case Immediate = 'immediate';
    case Digest = 'digest';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
