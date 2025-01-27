<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserNotificationChannel extends Model
{
    use HasFactory;

    protected $table = 'user_notification_channels';

    /**
     * Possible type.
     */
    public const TYPE_EMAIL = 'email';

    public const TYPE_TELEGRAM = 'telegram';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'label',
        'content',
        'active',
        'verified_at',
        'preferred_time',
        'verification_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'verified_at' => 'datetime',
        'preferred_time' => 'datetime',
    ];

    /**
     * Get the user associated with the user notification channel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user notification sent records associated with the user
     * notification channel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\UserNotificationSent, $this>
     */
    public function userNotificationSent(): HasMany
    {
        return $this->hasMany(UserNotificationSent::class);
    }

    /**
     * Get the contact reminder records associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\ContactReminder, $this>
     */
    public function contactReminders(): BelongsToMany
    {
        return $this->belongsToMany(ContactReminder::class, 'contact_reminder_scheduled')
            ->withPivot('scheduled_at', 'triggered_at')
            ->withTimestamps();
    }
}
