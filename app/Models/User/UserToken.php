<?php

namespace App\Models\User;

use App\Models\ModelBinding as Model;

class UserToken extends Model
{
    protected $table = 'usertokens';

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
        'user_id',
        'api_token',
        'dav_resource',
    ];
}
