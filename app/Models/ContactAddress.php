<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactAddress extends Model
{
    use HasFactory;

    protected $table = 'contact_addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'address_type_id',
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
     * Get the address's place.
     */
    public function place()
    {
        return $this->morphOne(Place::class, 'placeable');
    }
}
