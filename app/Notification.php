<?php

namespace App;

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

    public function scheduleForDeletion($number)
    {
        $this->delete_after_number_of_emails_sent = $number;
        $this->save();
    }

    public function checkIfCanBeDeletedAndProceedToDeletion()
    {
        // first, increment the counter of number of emails sent
        $this->number_of_emails_sent = $this->number_of_emails_sent + 1;
        $this->save();

        // then, if we've reached the number of emails required to delete
        // the notification, proceed to deletion
        if ($this->number_of_emails_sent == $this->number_of_emails_sent) {
            $this->delete();
        }
    }
}
