<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emotion extends Model
{
    use HasFactory;

    protected $table = 'emotions';

    /**
     * Possible category.
     */
    public const TYPE_POSITIVE = 'positive';

    public const TYPE_NEUTRAL = 'neutral';

    public const TYPE_NEGATIVE = 'negative';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'type',
    ];

    /**
     * Get the account associated with the emotion.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
