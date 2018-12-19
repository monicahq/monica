<?php

namespace App\Models\Contact;

use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Models\ModelBindingWithContact as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An Address is where the contact lives (or lived).
 * The actual address (street name etc…) is represented with a Place object.
 */
class Address extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'addresses';

    /**
     * Get the account record associated with the address.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the address.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact record associated with the address.
     *
     * @return BelongsTo
     */
    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}
