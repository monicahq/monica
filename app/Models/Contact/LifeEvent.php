<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeEvent extends Model
{
    protected $table = 'life_events';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'note',
        'happened_at',
        'account_id',
        'contact_id',
        'reminder_id',
        'life_event_type_id',
        'happened_at_month_unknown',
        'happened_at_day_unknown',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['happened_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'happened_at_month_unknown' => 'boolean',
        'happened_at_day_unknown' => 'boolean',
    ];

    /**
     * Get the account record associated with the life event.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the life event.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the life event type record associated with the life event.
     */
    public function lifeEventType()
    {
        return $this->belongsTo(LifeEventType::class, 'life_event_type_id');
    }

    /**
     * Get the reminder record associated with the life event.
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }
}
