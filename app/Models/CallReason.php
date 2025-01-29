<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * @var list<string>
     */
    protected $fillable = [
        'call_reason_type_id',
        'label',
        'label_translation_key',
    ];

    /**
     * Get the call reason type associated with call reason.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\CallReasonType, $this>
     */
    public function callReasonType(): BelongsTo
    {
        return $this->belongsTo(CallReasonType::class);
    }

    /**
     * Get the name of the reverse relationship attribute.
     * Call reasons entries have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                if ($value === null) {
                    return __($attributes['label_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
