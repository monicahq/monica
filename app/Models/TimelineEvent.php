<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * A contact's timeline is composed of many timeline events.
 * A timeline event is something that happened to one or more contacts.
 * A timeline event can cover multiple days, if needed, like "trip to antartica".
 * It is composed of one or more life events, like "drove 100 km", "ate pizza"
 * or whatever.
 */
class TimelineEvent extends Model
{
    use HasFactory;

    protected $table = 'timeline_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'started_at',
        'label',
        'collapsed',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collapsed' => 'boolean',
        'started_at' => 'datetime',
    ];

    /**
     * Get the vault associated with the timeline event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the life events associated with the timeline event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LifeEvent, $this>
     */
    public function lifeEvents(): HasMany
    {
        return $this->hasMany(LifeEvent::class);
    }

    /**
     * Get the contact records the timeline event is with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'timeline_event_participants', 'timeline_event_id', 'contact_id');
    }

    /**
     * Get the date range of the timeline event.
     * A timeline event can span over a long period of time.
     * While the `started_at` field is used to sort the timeline events,
     * we still need to know the date range of the event.
     *
     * @return Attribute<string,never>
     */
    protected function range(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $lifeEvents = $this->lifeEvents()->get();

                if ($lifeEvents->count() === 0) {
                    return '';
                }

                $firstEvent = $lifeEvents->sortBy('happened_at')->first();
                $lastEvent = $lifeEvents->sortByDesc('happened_at')->first();

                if ($firstEvent->id === $lastEvent->id) {
                    return DateHelper::format($firstEvent->happened_at, Auth::user());
                }

                return DateHelper::format($firstEvent->happened_at, Auth::user()).
                    ' — '.
                    DateHelper::format($lastEvent->happened_at, Auth::user());
            }
        );
    }
}
