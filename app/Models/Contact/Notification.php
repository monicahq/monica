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

use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notification is an email that is sent some days before a reminder is
 * actually sent. Those days are defined by reminder rules.
 */
class Notification extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'notifications';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['trigger_date'];

    /**
     * Get the account record associated with the notification.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the notification.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the reminder record associated with the notification.
     *
     * @return BelongsTo
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    /**
     * Indicates the number of emails should be sent before a notification
     * has to be deleted. This is required as a notification can be sent to
     * multiple users in the same account. The number of of emails will match
     * the number of users in the account.
     *
     * @param  int $number
     * @return void
     */
    public function setNumberOfEmailsNeededForDeletion($number)
    {
        $this->delete_after_number_of_emails_sent = $number;
        $this->save();
    }

    /**
     * Check if a notification can be deleted. A notification can be sent to
     * multiple users in the same account, so we need to check that all emails
     * have been sent before deleting the notification.
     *
     * @return void
     */
    public function incrementNumberOfEmailsSentAndCheckDeletioNStatus()
    {
        // first, increment the counter of number of emails sent
        $this->number_of_emails_sent = $this->number_of_emails_sent + 1;
        $this->save();

        // then, if we've reached the number of emails required to delete
        // the notification, proceed to deletion
        if ($this->delete_after_number_of_emails_sent == $this->number_of_emails_sent) {
            $this->delete();
        }
    }

    /**
     * Indicate whether a notification should be sent, as this should be
     * dictated by the reminder rule (on or off).
     *
     * @return bool
     */
    public function shouldBeSent()
    {
        $reminderRule = $this->account->reminderRules()
                            ->where('number_of_days_before', $this->scheduled_number_days_before)
                            ->first();

        if (! $reminderRule) {
            return false;
        }

        return $reminderRule->active;
    }
}
