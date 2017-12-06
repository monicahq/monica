<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * A special date is a date that is not necessarily based on a year or even a
 * date with a month or a day.
 * This happens when we add a birthdate for instance. It can be based:
 *     * on a real date, where we know the day, month and year
 *     * on a date where we just know the day and the month but not the year
 *     * on an age (we know this person is 33 but we don't know his birthdate,
 *       so we'll give an estimation)
 *
 * Instead of adding a lot of logic in the Contact table, we've decided to
 * create this class that will deal with this complexity.
 */
class SpecialDate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'special_dates';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_age_based' => 'boolean',
        'is_year_unknown' => 'boolean',
    ];

    /**
     * Get the account record associated with the gift.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    public function setAnnualReminder()
    {

    }

    public function deleteAnnualReminder()
    {

    }

    public function getAge()
    {
        if (is_null($this->date)) {
            return;
        }

        return $this->date->diffInYears(Carbon::now());
    }

    /**
     * Create a SpecialDate from an age.
     * @param  int $age
     */
    public static function createFromAge(int $age)
    {
        $this->is_age_based = true;
        $this->date = Carbon::now()->subYears($age)->month(1)->day(1);
        $this->save();
    }

    /**
     * Create a SpecialDate from an actual date, that might not contain a year.
     * @param  int    $year
     * @param  int    $month
     * @param  int    $day
     */
    public static function createFromDate(int $year, int $month, int $day)
    {
        if ($year) {
            $dateOfBirth = Carbon::createFromDate($year, $month, $day);
        } else {
            $dateOfBirth = Carbon::createFromDate(Carbon::now()->year, $month, $day);
            $this->is_year_unknown = true;
        }

        $this->date = $dateOfBirth;
        $this->save();
    }
}
