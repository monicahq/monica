<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * A relationship defines relations between contacts.
 */
class Relationship extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'contact_id',
        'with_contact_id',
        'is_active',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
