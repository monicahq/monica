<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'label',
        'label_translation_key',
    ];

    /**
     * Get the account associated with the call reason type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the call reasons associated with the call reason type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\CallReason, $this>
     */
    public function callReasons(): HasMany
    {
        return $this->hasMany(CallReason::class);
    }

    /**
     * Get the name of the reverse relationship attribute.
     * Call reason types entries have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return __($attributes['label_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
