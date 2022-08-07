<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSent extends Model
{
    use HasFactory;

    protected $table = 'user_notification_sent';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_notification_channel_id',
        'sent_at',
        'subject_line',
        'payload',
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
    public function notificationChannel(): BelongsTo
    {
        return $this->belongsTo(UserNotificationChannel::class, 'user_notification_channel_id');
    }
}
