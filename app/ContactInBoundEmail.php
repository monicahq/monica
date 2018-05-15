<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactInBoundEmail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'contact_id',
        'email_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function email()
    {
        return $this->belongsTo(InBoundEmail::class, 'email_id');
    }
}
