<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoodTrackingParameter extends Model
{
    use HasFactory;

    protected $table = 'mood_tracking_parameters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'vault_id',
        'label',
        'label_translation_key',
        'hex_color',
        'position',
    ];

    /**
     * Get the vault associated with the mood tracking parameter.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the mood tracking events associated with the mood tracking parameter.
     *
     * @return HasMany
     */
    public function moodTrackingEvents(): HasMany
    {
        return $this->hasMany(MoodTrackingEvent::class);
    }

    /**
     * Get the label attribute.
     * A mood tracking parameter has a default label that can be translated.
     * Howerer, if a label is set, it will be used instead of the default.
     *
     * @return Attribute<string,never>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return trans($attributes['label_translation_key']);
                }

                return $value;
            }
        );
    }
}
