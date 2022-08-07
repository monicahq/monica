<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallReason extends Model
{
    use HasFactory;

    protected $table = 'call_reasons';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'call_reason_type_id',
        'label',
    ];

    /**
     * Get the call reason type associated with call reason.
     *
     * @return BelongsTo
     */
    public function callReasonType(): BelongsTo
    {
        return $this->belongsTo(CallReasonType::class);
    }
}
