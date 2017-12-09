<?php

namespace App;

use Auth;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $contact
 */
class Reminder extends Model
{
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
    protected $dates = ['last_triggered', 'next_expected_date'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_birthday' => 'boolean',
    ];

    /**
     * Get the account record associated with the reminder.
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
     * Get the next_expected_date field according to user's timezone.
     *
     * @param string $value
     * @return string
     */
    public function getNextExpectedDateAttribute($value)
    {
        if (auth()->user()) {
            return Carbon::parse($value, auth()->user()->timezone);
        }

        return Carbon::parse($value);
    }

    /**
     * Correctly set the frequency type.
     *
     * @param string $value
     */
    public function setFrequencyTypeAttribute($value)
    {
        $this->attributes['frequency_type'] = $value === 'once' ? 'one_time' : $value;
    }

    /**
     * Get the title of a reminder.
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value;
    }

    /**
     * Set the title of a reminder.
     * @return string
     */
    public function setTitleAttribute($title)
    {
        $this->attributes['title'] = $title;
    }

    /**
     * Get the description of a reminder.
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return $value;
    }

    /**
     * Return the next expected date.
     *
     * @return string
     */
    public function getNextExpectedDate()
    {
        return $this->next_expected_date->format('Y-m-d');
    }

    /**
     * Calculate the next expected date for this reminder.
     *
     * @return static
     */
    public function calculateNextExpectedDate($timezone)
    {
        $date = $this->next_expected_date->setTimezone($timezone);

        while ($date->isPast()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        if ($date->isToday()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        $this->next_expected_date = $date;

        return $this;
    }
}
