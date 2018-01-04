<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSocial extends Model
{
    protected $table = 'user_social';

    protected $fillable = [
        'social_id', 'service', 'access_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
