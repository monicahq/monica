<?php

namespace App\Models\Contact;

use Carbon\Carbon;
use App\Traits\HasUuid;
use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModelBindingHasherWithContact as Model;

/**
 * A reminder has two states: active and inactive.
 * An inactive reminder is basically a one_time reminder that has already be
 * sent once and has been marked inactive so we don't schedule it again.
 *
 * @property string $next_expected_date_human_readable
 * @property string $next_expected_date
 */
class Reminder extends Model
{
    use HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_birthday' => 'boolean',
        'delible' => 'boolean',
        'inactive' => 'boolean',
        'initial_date' => 'date:Y-m-d',
    ];

    /**
     * Valid value for frequency type.
     *
     * @var array
     */
    public static $frequencyTypes = [
        'one_time', 'week', 'month', 'year',
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
     * Scope a query to only include active reminders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('inactive', false);
    }

    /**
     * Test if this reminder is the contact's birthday reminder.
     *
     * @return bool
     */
    public function isBirthdayReminder(): bool
    {
        return $this->contact !== null
            && $this->contact->birthday_reminder_id === $this->id;
    }

    /**
     * Calculate the next expected date for this reminder.
     *
     * @return Carbon
     */
    public function calculateNextExpectedDate($date = null)
    {
        if (is_null($date)) {
            $date = $this->initial_date;
        }

        while ($date->isPast()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        if ($date->isToday()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        return $date;
    }

    /**
     * Calculate the next expected date using user timezone for this reminder.
     *
     * @return Carbon
     */
    public function calculateNextExpectedDateOnTimezone()
    {
        $date = $this->initial_date;
        $date = Carbon::create($date->year, $date->month, $date->day, 0, 0, 0,
                    DateHelper::getTimezone() ?? config('app.timezone'));

        return $this->calculateNextExpectedDate($date);
    }

    /**
     * Schedule the reminder to be sent.
     *
     * @param  User  $user
     * @return void
     */
    public function schedule(User $user)
    {
        // remove any existing scheduled reminders
        $this->reminderOutboxes->each->delete();

        // when should we send this reminder?
        $triggerDate = $this->calculateNextExpectedDate();

        // schedule the reminder in the outbox, one for each user of the account
        ReminderOutbox::create([
            'account_id' => $this->account_id,
            'reminder_id' => $this->id,
            'user_id' => $user->id,
            'planned_date' => $triggerDate,
            'nature' => 'reminder',
        ]);

        $this->scheduleNotifications($triggerDate, $user);
    }

    /**
     * Create all the notifications that are supposed to be sent
     * 30 and 7 days prior to the actual reminder.
     *
     * @param  Carbon  $triggerDate
     * @param  User  $user
     * @return void
     */
    public function scheduleNotifications(Carbon $triggerDate, User $user)
    {
        $date = $triggerDate->toDateString();
        $reminderRules = $this->account->reminderRules()->where('active', 1)->get();

        foreach ($reminderRules as $reminderRule) {
            $datePrior = Carbon::createFromFormat('Y-m-d', $date)
                ->subDays($reminderRule->number_of_days_before);

            if ($datePrior->lessThanOrEqualTo(now())) {
                continue;
            }

            ReminderOutbox::create([
                'account_id' => $this->account_id,
                'reminder_id' => $this->id,
                'user_id' => $user->id,
                'planned_date' => $datePrior->toDateString(),
                'nature' => 'notification',
                'notification_number_days_before' => $reminderRule->number_of_days_before,
            ]);
        }
    }
}
