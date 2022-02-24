<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserNotificationChannel extends Model
{
    use HasFactory;

    protected $table = 'user_notification_channels';

    /**
     * Possible type.
     */
    const TYPE_EMAIL = 'email';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type',
        'label',
        'content',
        'active',
        'verified_at',
        'preferred_time',
        'email_verification_link',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'verified_at',
    ];

    /**
     * Get the user associated with the user notification channel.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user notification sent records associated with the user
     * notification channel.
     *
     * @return HasMany
     */
    public function userNotificationSent()
    {
        return $this->hasMany(UserNotificationSent::class);
    }
}
