<?php

namespace App\Models\Contact;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use App\Models\Account\Account;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModelBindingHasherWithContact as Model;

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
    protected $dates = [
        'last_triggered',
        'next_expected_date',
        'initial_date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_birthday' => 'boolean',
    ];

    /**
     * Valids value for frequency type.
     *
     * @var array
     */
    public static $frequencyTypes = [
        'one_time', 'day', 'month', 'year',
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
     * Get the Reminder Outbox records associated with the account.
     *
     * @return HasMany
     */
    public function reminderOutboxes()
    {
        return $this->hasMany(ReminderOutbox::class);
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
     *
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value;
    }

    /**
     * Set the title of a reminder.
     *
     * @return string
     */
    public function setTitleAttribute($title)
    {
        $this->attributes['title'] = $title;
    }

    /**
     * Get the description of a reminder.
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return $value;
    }

    /**
     * Calculate the next expected date for this reminder.
     *
     * @return Carbon
     */
    public function calculateNextExpectedDate()
    {
        $date = $this->initial_date;

        while ($date->isPast()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        if ($date->isToday()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        return $date;
    }

    /**
     * Schedule the reminder to be sent.
     *
     * @return void
     */
    public function schedule()
    {
        // when should we send this reminder?
        $triggerDate = $this->calculateNextExpectedDate();

        $this->next_expected_date = $triggerDate;
        $this->save();

        // remove any existing scheduled reminders
        $this->reminderOutboxes->each->delete();

        // schedule the reminder in the outbox
        ReminderOutbox::create([
            'account_id' => $this->account_id,
            'reminder_id' => $this->id,
            'planned_date' => $triggerDate,
            'nature' => 'reminder',
        ]);

        $this->scheduleNotifications($triggerDate);
    }

    /**
     * Create all the notifications that are supposed to be sent
     * 30 and 7 days prior to the actual reminder.
     *
     * @param Carbon $triggerDate
     * @return void
     */
    public function scheduleNotifications(Carbon $triggerDate)
    {
        $date = $triggerDate->toDateString();
        $reminderRules = $this->account->reminderRules()->where('active', 1)->get();

        foreach ($reminderRules as $reminderRule) {
            $datePrior = Carbon::createFromFormat('Y-m-d', $date);
            $datePrior->subDays($reminderRule->number_of_days_before);

            if ($datePrior->lessThanOrEqualTo(now())) {
                continue;
            }

            ReminderOutbox::create([
                'account_id' => $this->account_id,
                'reminder_id' => $this->id,
                'planned_date' => $datePrior->toDateString(),
                'nature' => 'notification',
                'notification_number_days_before' => $reminderRule->number_of_days_before,
            ]);
        }
    }
}
