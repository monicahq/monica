<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Models\Contact;

use App\Helpers\DateHelper;
use App\Models\Account\Account;
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
     * Get the Notifications records associated with the account.
     *
     * @return HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the next_expected_date field according to user's timezone.
     *
     * @param string $value
     * @return string
     */
    public function getNextExpectedDateAttribute($value)
    {
        return DateHelper::parseDate($value);
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
        return $this->next_expected_date->toDateString();
    }

    /**
     * Calculate the next expected date for this reminder.
     *
     * @return static
     */
    public function calculateNextExpectedDate()
    {
        $date = $this->next_expected_date;

        while ($date->isPast()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        if ($date->isToday()) {
            $date = DateHelper::addTimeAccordingToFrequencyType($date, $this->frequency_type, $this->frequency_number);
        }

        $this->next_expected_date = $date;

        return $this;
    }

    /**
     * Schedules the notifications for the given reminder.
     *
     * @return void
     */
    public function scheduleNotifications()
    {
        if ($this->frequency_type == 'week') {
            return;
        }

        // Only schedule notifications for active reminder rules
        $reminderRules = $this->account->reminderRules()->where('active', 1)->get();

        foreach ($reminderRules as $reminderRule) {
            $this->scheduleSingleNotification($reminderRule->number_of_days_before);
        }
    }

    /**
     * Schedules a notification for the given reminder.
     *
     * @param  int  $numberOfDaysBefore
     * @return Notification
     */
    public function scheduleSingleNotification(int $numberOfDaysBefore)
    {
        $date = DateHelper::getDateMinusGivenNumberOfDays($this->next_expected_date, $numberOfDaysBefore);

        if ($date->lte(now())) {
            return;
        }

        $notification = new Notification;
        $notification->account_id = $this->account_id;
        $notification->contact_id = $this->contact_id;
        $notification->reminder_id = $this->id;
        $notification->trigger_date = $date;
        $notification->scheduled_number_days_before = $numberOfDaysBefore;
        $notification->save();

        return $notification;
    }

    /**
     * Purge all the existing notifications for a reminder.
     *
     * @return void
     */
    public function purgeNotifications()
    {
        $this->notifications->each->delete();
    }
}
