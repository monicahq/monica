<?php

namespace App\Models\Instance;

use Illuminate\Database\Eloquent\Model;

class Cron extends Model
{
    protected $primaryKey = 'command';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'command',
        'last_run',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_run' => 'datetime',
    ];
}
