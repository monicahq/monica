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
        'account_id',
        'user_id',
        'recovery',
    ];

    /**
     * Scope a query to only include unused code.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnused($query)
    {
        return $query->where('used', 0);
    }
}
