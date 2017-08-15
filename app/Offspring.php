<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * A relationship defines relations between contacts.
 */
class Offspring extends Model
{
    protected $table = 'offsprings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'contact_id',
        'is_the_child_of',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
