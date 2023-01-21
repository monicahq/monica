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
     * @var array<string>
     */
    protected $fillable = [
        'contact_id',
        'mood_tracking_parameter_id',
        'rated_at',
        'note',
        'number_of_hours_slept',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'rated_at',
    ];

    /**
     * Get the contact associated with the mood tracking event.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the mood tracking parameter associated with the mood tracking event.
     *
     * @return BelongsTo
     */
    public function moodTrackingParameter(): BelongsTo
    {
        return $this->belongsTo(MoodTrackingParameter::class);
    }

    /**
     * Get the mood tracking event's feed item.
     *
     * @return MorphOne
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
