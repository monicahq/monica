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
        'triggered_at',
        'triggered',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'triggered_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'triggered' => 'boolean',
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
     * Get the user associated with the scheduled contact reminder.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
