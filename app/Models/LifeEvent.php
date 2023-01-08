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
        'vault_id',
        'life_event_type_id',
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
     * Get the vault associated with the life event.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
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
