<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MoodTrackingEvent extends Model
{
    use HasFactory;

    protected $table = 'mood_tracking_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'contact_id',
        'mood_tracking_parameter_id',
        'rated_at',
        'note',
        'number_of_hours_slept',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rated_at' => 'datetime',
    ];

    /**
     * Get the contact associated with the mood tracking event.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the mood tracking parameter associated with the mood tracking event.
     */
    public function moodTrackingParameter(): BelongsTo
    {
        return $this->belongsTo(MoodTrackingParameter::class);
    }

    /**
     * Get the mood tracking event's feed item.
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
