<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Whether a string is a local time of day a rule can fire at.
 *
 * The form already asks for `H:i` and refuses anything else, so this is the
 * backstop for every caller that is not the form. An action storing a time
 * nothing can parse gives a rule that quietly never produces a deadline, which
 * is the worst way for a reminder to fail: it looks set up and does nothing.
 *
 * Seconds are accepted because that is how the column reads back, so a value
 * taken off a rule and handed straight to another action is not refused for
 * having been through the database.
 */
class ReminderTime
{
    public static function isATime(string $time): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time) === 1;
    }
}
