<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledContactReminder extends Model
{
    use HasFactory;

    protected $table = 'scheduled_contact_reminders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_reminder_id',
        'user_notification_channel_id',
        'scheduled_at',
        'triggered_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'scheduled_at',
        'triggered_at',
    ];

    /**
     * Get the contact reminder associated with the scheduled contact reminder.
     *
     * @return BelongsTo
     */
    public function reminder()
    {
        return $this->belongsTo(ContactReminder::class, 'contact_reminder_id');
    }

    /**
     * Get the user notification channel associated with the scheduled contact reminder.
     *
     * @return BelongsTo
     */
    public function userNotificationChannel()
    {
        return $this->belongsTo(UserNotificationChannel::class);
    }
}
