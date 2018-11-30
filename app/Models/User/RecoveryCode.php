<?php

namespace App\Models\User;

use App\Models\ModelBinding as Model;

class RecoveryCode extends Model
{
    protected $table = 'recovery_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'recovery',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
