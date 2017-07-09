<?php

namespace App;

use Auth;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MartinJoiner\OrdinalNumber\OrdinalNumber;

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
     * Get the next_expected_date field according to user's timezone
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
     * Correctly set the frequency type
     *
     * @param string $value
     */
    public function setFrequencyTypeAttribute($value)
    {
        $this->attributes['frequency_type'] = $value === 'once' ? 'one_time' : $value;
    }

    /**
     * Add a new birthday reminder
     *
     * @param Contact $contact
     * @param string $title
     * @param Carbon|string $date
     * @param Kid $kid
     * @param SignificantOther $kid
     * @return static
     */
    public static function addBirthdayReminder($contact, $title, $date, $kid = null, $significantOther = null)
    {
        $date = Carbon::parse($date);

        $reminder = $contact->reminders()
            ->create([
                'title' => $title,
                'frequency_type' => 'year',
                'frequency_number' => 1,
                'next_expected_date' => $date,
                'account_id' => $contact->account_id,
                'is_birthday' => 'true',
                'about_object' => $kid ? 'kid' : ($significantOther ? 'significantother' : 'contact'),
                'about_object_id' => $kid ? $kid->id : ($significantOther ? $significantOther->id : $contact->id)
            ]);

        $reminder->calculateNextExpectedDate($date, 'year', 1)
            ->save();

        return $reminder;
    }

    /**
     * Get the title of a reminder.
     * @return string
     */
    public function getTitle()
    {
        if (is_null($this->title)) {
            return null;
        }

        return $this->title;
    }

    /**
     * Get the description of a reminder.
     * @return string
     */
    public function getDescription()
    {
        if (is_null($this->description)) {
            return null;
        }

        return $this->description;
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
     * Calculate the next expected date for this reminder based on the current
     * start date.
     * @param  Carbon $startDate
     * @param  string $frequencyTYpe
     * @param  int $frequencyNumber
     * @return static
     */
    public function calculateNextExpectedDate($startDate, $frequencyType, $frequencyNumber)
    {
        if ($startDate->isToday()) {
            $nextDate = DateHelper::calculateNextOccuringDate($startDate, $frequencyType, $frequencyNumber);
            $this->next_expected_date = $nextDate;
        }

        if ($startDate >= $startDate->tomorrow()) {
            $this->next_expected_date = $startDate;
        } else {

            // Date is in the past, we need to extract the month and day, and
            // setup the next occurence at those dates.
            $nextDate = DateHelper::calculateNextOccuringDate($startDate, $frequencyType, $frequencyNumber);

            while ($nextDate->isPast()) {
                $nextDate = DateHelper::calculateNextOccuringDate($nextDate, $frequencyType, $frequencyNumber);
            }

            // This is the case where we set the date in the past, but the next
            // occuring date is still today, so we make sure it skips to the
            // next occuring date.
            if ($startDate->isToday()) {
                $nextDate = DateHelper::calculateNextOccuringDate($startDate, $frequencyType, $frequencyNumber);
            }
            $this->next_expected_date = $nextDate;
        }

        return $this;
    }
}
