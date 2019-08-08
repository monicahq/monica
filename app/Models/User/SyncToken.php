<?php

namespace App\Models\User;

use App\Models\ModelBinding as Model;

class SyncToken extends Model
{
    protected $table = 'synctoken';

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
        'account_id',
        'user_id',
        'name',
        'timestamp',
    ];
}
