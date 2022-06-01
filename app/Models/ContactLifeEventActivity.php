<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ContactLifeEventActivity extends Model
{
    use HasFactory;

    protected $table = 'contact_life_event_activities';

    /**
     * Possible type.
     */
    const TYPE_PERIOD_FULL_DAY = 'full day';
    const TYPE_PERIOD_MORNING = 'morning';
    const TYPE_PERIOD_AFTERNOON = 'afternoon';
    const TYPE_PERIOD_EVENING = 'evening';
    const TYPE_PERIOD_NIGHT = 'all night';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'life_event_id',
        'activity_id',
        'emotion_id',
        'summary',
        'description',
        'happened_at',
        'period_of_day',
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
     * Get the vault associated with the contact activity.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the activity associated with the contact activity.
     *
     * @return BelongsTo
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the emotion associated with the call.
     *
     * @return BelongsTo
     */
    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }

    /**
     * Get the contact records the activity is with.
     *
     * @return BelongsToMany
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_activity_participants', 'contact_activity_id', 'contact_id');
    }

    /**
     * Get the contact activity's feed item.
     *
     * @return MorphOne
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
