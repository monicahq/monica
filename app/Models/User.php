<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasFactory, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'is_account_administrator',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_account_administrator' => 'boolean',
    ];

    /**
     * Get the account record associated with the user.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault records associated with the user.
     *
     * @return BelongsToMany
     */
    public function vaults()
    {
        return $this->belongsToMany(Vault::class)->withTimestamps()->withPivot('permission');
    }

    /**
     * Get the note records associated with the user.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'author_id');
    }

    /**
     * Get the name of the user.
     *
     * @param mixed $value
     * @return string
     */
    public function getNameAttribute($value): string
    {
        return $this->first_name.' '.$this->last_name;
    }
}
