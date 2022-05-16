<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'address_type_id',
        'street',
        'city',
        'province',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'lived_from_at',
        'lived_until_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'lived_from_at',
        'lived_until_at',
    ];

    /**
     * Get the contact associated with the address.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the address type object associated with the address.
     *
     * @return BelongsTo
     */
    public function addressType()
    {
        return $this->belongsTo(AddressType::class);
    }
}
