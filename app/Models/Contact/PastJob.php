<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;

class PastJob extends Model
{
    protected $fillable = [
        'contact_id',
        'past_company',
        'past_job'
    ];

    public function pastJob()
    {
        return $this->belongsTo('App\Models\Contact\Contact');
    }
    
}
