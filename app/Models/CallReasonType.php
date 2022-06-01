<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallReasonType extends Model
{
    use HasFactory;

    protected $table = 'call_reason_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'label',
    ];

    /**
     * Get the account associated with the call reason type.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the call reasons associated with the call reason type.
     *
     * @return HasMany
     */
    public function callReasons(): HasMany
    {
        return $this->hasMany(CallReason::class);
    }
}
