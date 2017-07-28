<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * A relationship defines relations between contacts.
 */
class Relationship extends Model
{
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
