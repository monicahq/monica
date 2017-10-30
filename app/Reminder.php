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
     * Add a new birthday reminder.
     *
     * @param Contact $contact
     * @param Carbon|string $date
     * @return Reminder
     */
    public static function addBirthdayReminder(Contact $contact, $birthdate)
    {
        $birthdate = Carbon::parse($birthdate);

        $reminder = $contact->reminders()
            ->create([
                'frequency_type' => 'year',
                'frequency_number' => 1,
                'next_expected_date' => $birthdate,
                'account_id' => $contact->account_id,
                'is_birthday' => true,
            ]);

        foreach ($contact->account->users as $user) {
            $userTimezone = $user->timezone;
        }

        $reminder->calculateNextExpectedDate($userTimezone)->save();

        return $reminder;
    }

    /**
     * Get the title of a reminder.
     * @return string
     */
    public function getTitle()
    {
        if ($this->is_birthday) {
            // we need to construct the title of the reminder as in the case of a
            // birthday, the title field is null
            return trans('people.reminders_birthday', ['name' => $this->contact->first_name]);
        }

        if (is_null($this->title)) {
            return;
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
            return;
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
