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
     * @var list<string>
     */
    protected $fillable = [
        'user_notification_channel_id',
        'sent_at',
        'subject_line',
        'payload',
        'error',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the user notification channel associated with the user notification sent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\UserNotificationChannel, $this>
     */
    public function notificationChannel(): BelongsTo
    {
        return $this->belongsTo(UserNotificationChannel::class, 'user_notification_channel_id');
    }
}
