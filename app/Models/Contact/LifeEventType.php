<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeEventType extends Model
{
    use HasUuid;

    protected $table = 'life_event_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'account_id',
        'life_event_category_id',
        'default_life_event_type_key',
        'core_monica_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'core_monica_data' => 'boolean',
    ];

    /**
     * Get the account record associated with the life event type.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the life event category record associated with the life event type.
     *
     * @return BelongsTo
     */
    public function lifeEventCategory()
    {
        return $this->belongsTo(LifeEventCategory::class, 'life_event_category_id');
    }

    /**
     * Get the Life event records associated with the life event Type.
     *
     * @return HasMany
     */
    public function lifeEvents()
    {
        return $this->hasMany(LifeEvent::class);
    }
}
