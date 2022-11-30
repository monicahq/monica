<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncToken extends Model
{
    use HasFactory;

    protected $table = 'synctokens';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
