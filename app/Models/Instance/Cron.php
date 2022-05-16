<?php

namespace App\Models\Instance;

use Illuminate\Database\Eloquent\Model;

class Cron extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'command',
        'last_run',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_run' => 'datetime',
    ];
}
