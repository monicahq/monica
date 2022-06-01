<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ContactLifeEvent extends Model
{
    use HasFactory;

    protected $table = 'contact_life_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'life_event_type_id',
        'summary',
        'started_at',
        'ended_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'started_at',
        'ended_at',
    ];

    /**
     * Get the contact associated with the life event.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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
     * Get the life event's feed item.
     *
     * @return MorphOne
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
