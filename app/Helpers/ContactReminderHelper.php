<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ContactReminder;

class ContactReminderHelper
{
    /**
     * Get the date as a string, based on the prefered date format of the given
     * user.
     *
     * @param  ContactReminder  $reminder
     * @param  User  $user
     * @return string|null
     */
    public static function formatDate(ContactReminder $reminder, User $user): ?string
    {
        if (! $reminder->day && ! $reminder->month && ! $reminder->year) {
            return null;
        }

        if (! $reminder->day && ! $reminder->month && $reminder->year) {
            return null;
        }

        if ($reminder->day && $reminder->month && ! $reminder->year) {
            $carbonDate = Carbon::parse('1900-'.$reminder->month.'-'.$reminder->day);

            switch ($user->date_format) {
                case 'MMM DD, YYYY':
                    $reminderAsString = Carbon::parse($carbonDate)->isoFormat('MMM DD');
                    break;

                case 'DD MMM YYYY':
                    $reminderAsString = Carbon::parse($carbonDate)->isoFormat('DD MMM');
                    break;

                case 'YYYY/MM/DD':
                    $reminderAsString = Carbon::parse($carbonDate)->isoFormat('MM/DD');
                    break;

                case 'DD/MM/YYYY':
                    $reminderAsString = Carbon::parse($carbonDate)->isoFormat('DD/MM');
                    break;

                default:
                    $reminderAsString = Carbon::parse($carbonDate)->isoFormat('DD/MM');
                    break;
            }
        }

        if ($reminder->day && $reminder->month && $reminder->year) {
            $reminderAsString = Carbon::parse($reminder->year.'-'.$reminder->month.'-'.$reminder->day)->isoFormat($user->date_format);
        }

        return $reminderAsString;
    }
}
