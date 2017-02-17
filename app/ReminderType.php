<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReminderType extends Model
{
    protected $table = 'reminder_types';

    /**
     * Get the birthday reminder type id.
     * @return int
     */
    public static function getBirthdayTypeID()
    {
        $reminderType = self::where('description', 'birthday')->first();

        return $reminderType->id;
    }

    /**
     * Get the birthday kid reminder type id.
     * @return int
     */
    public static function getBirthdayKidTypeID()
    {
        $reminderType = self::where('description', 'birthday_kid')->first();

        return $reminderType->id;
    }
}
