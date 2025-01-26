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
     * @var list<string>
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
        'distance',
        'distance_unit',
        'from_place',
        'to_place',
        'place',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collapsed' => 'boolean',
        'happened_at' => 'datetime',
    ];

    /**
     * Get the timeline event associated with the life event.
     */
    public function timelineEvent(): BelongsTo
    {
        return $this->belongsTo(TimelineEvent::class);
    }

    /**
     * Get the life event type associated with the life event.
     */
    public function lifeEventType(): BelongsTo
    {
        return $this->belongsTo(LifeEventType::class);
    }

    /**
     * Get the currency associated with the life event.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the emotion associated with the life event.
     */
    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }

    /**
     * Get the contact who paid for the life event.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'paid_by_contact_id');
    }

    /**
     * Get the contact records the life event is with.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'life_event_participants', 'life_event_id', 'contact_id');
    }
}
