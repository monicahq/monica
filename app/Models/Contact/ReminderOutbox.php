<?php

namespace App\Models\Contact;

use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModelBindingHasherWithContact as Model;

/**
 * @property Account $account
 * @property int $account_id
 * @property Contact $contact
 * @property User $user
 * @property int $user_id
 * @property Reminder|null $reminder
 * @property int $reminder_id
 * @property string $nature
 * @property \Illuminate\Support\Carbon|null $planned_date
 * @property int $notification_number_days_before
 */
class ReminderOutbox extends Model
{
    protected $table = 'reminder_outbox';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'planned_date',
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
     * Get the reminder record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    /**
     * Get the user record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
