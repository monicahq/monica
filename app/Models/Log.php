<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group_id',
        'level',
        'level_name',
        'channel',
        'message',
        'context',
        'extra',
        'formatted',
        'logged_at',
        'loggable_type',
        'loggable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'context' => 'json',
        'extra' => 'json',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the loggable entry.
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
