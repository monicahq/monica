<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactReminder extends Model
{
    use HasFactory;

    protected $table = 'contact_reminders';

    /**
     * Possible type.
     */
    public const TYPE_ONE_TIME = 'one_time';
    public const TYPE_RECURRING_DAY = 'recurring_day';
    public const TYPE_RECURRING_MONTH = 'recurring_month';
    public const TYPE_RECURRING_YEAR = 'recurring_year';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'label',
        'day',
        'month',
        'year',
        'type',
        'frequency_number',
        'last_triggered_at',
        'number_times_triggered',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'last_triggered_at',
    ];

    /**
     * Get the contact associated with the contact reminder.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user notification channel records associated with the contact reminder.
     *
     * @return BelongsToMany
     */
    public function userNotificationChannels(): BelongsToMany
    {
        return $this->belongsToMany(UserNotificationChannel::class, 'contact_reminder_scheduled')->withTimestamps()->withPivot('scheduled_at', 'triggered');
    }
}
