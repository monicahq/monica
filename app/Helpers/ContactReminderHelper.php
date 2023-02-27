<?php

namespace App\Helpers;

use App\Models\ContactReminder;
use App\Models\User;
use Carbon\Carbon;

class ContactReminderHelper
{
    /**
     * Get the date as a string, based on the preferred date format of the given
     * user.
     */
    public static function formatDate(ContactReminder $reminder, User $user): ?string
    {
        $reminderAsString = '';

        if (! $reminder->day && ! $reminder->month && ! $reminder->year) {
            return null;
        }

        if (! $reminder->day && ! $reminder->month && $reminder->year) {
            return null;
        }

        if ($reminder->day && $reminder->month && ! $reminder->year) {
            $carbonDate = Carbon::parse('1900-'.$reminder->month.'-'.$reminder->day);

            $reminderAsString = match ($user->date_format) {
                'MMM DD, YYYY' => Carbon::parse($carbonDate)->isoFormat('MMM DD'),
                'DD MMM YYYY' => Carbon::parse($carbonDate)->isoFormat('DD MMM'),
                'YYYY/MM/DD' => Carbon::parse($carbonDate)->isoFormat('MM/DD'),
                default => Carbon::parse($carbonDate)->isoFormat('DD/MM'),
            };
        }

        if ($reminder->day && $reminder->month && $reminder->year) {
            $reminderAsString = Carbon::parse($reminder->year.'-'.$reminder->month.'-'.$reminder->day)->isoFormat($user->date_format);
        }

        return $reminderAsString;
    }
}
