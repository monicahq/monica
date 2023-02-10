<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LifeEvent extends Model
{
    use HasFactory;

    protected $table = 'life_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'timeline_event_id',
        'life_event_type_id',
        'emotion_id',
        'collapsed',
        'summary',
        'description',
        'happened_at',
        'costs',
        'currency_id',
        'paid_by_contact_id',
        'duration_in_minutes',
        'distance_in_km',
        'from_place',
        'to_place',
        'place',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'happened_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collapsed' => 'boolean',
    ];

    /**
     * Get the timeline event associated with the life event.
     *
     * @return BelongsTo
     */
    public function timelineEvent(): BelongsTo
    {
        return $this->belongsTo(TimelineEvent::class);
    }

    /**
     * Get the life event type associated with the life event.
     *
     * @return BelongsTo
     */
    public function lifeEventType(): BelongsTo
    {
        return $this->belongsTo(LifeEventType::class, 'life_event_type_id');
    }

    /**
     * Get the currency associated with the life event.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the emotion associated with the life event.
     *
     * @return BelongsTo
     */
    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }

    /**
     * Get the contact who paid for the life event.
     *
     * @return BelongsTo
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'paid_by_contact_id');
    }

    /**
     * Get the contact records the life event is with.
     *
     * @return BelongsToMany
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'life_event_participants', 'life_event_id', 'contact_id');
    }
}
