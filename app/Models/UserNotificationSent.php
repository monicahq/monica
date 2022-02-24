<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserNotificationSent extends Model
{
    use HasFactory;

    protected $table = 'user_notification_sent';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_notification_channel_id',
        'sent_at',
        'subject_line',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'sent_at',
    ];

    /**
     * Get the user notification channel associated with the user notification sent.
     *
     * @return BelongsTo
     */
    public function notificationChannel()
    {
        return $this->belongsTo(UserNotificationChannel::class, 'user_notification_channel_id');
    }
}
