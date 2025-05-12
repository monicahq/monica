<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'driver',
        'driver_id',
        'email',
        'format',
        'token',
        'token_secret',
        'refresh_token',
        'expires_in',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'token',
        'token_secret',
        'refresh_token',
    ];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var list<string>
     */
    protected $visible = [
        'driver',
        'email',
        'format',
    ];

    /**
     * Get the user record associated with the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
