<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeEventType extends Model
{
    use HasFactory;

    protected $table = 'life_event_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'life_event_category_id',
        'label',
        'label_translation_key',
        'can_be_deleted',
        'position',
    ];

    /**
     * Get the life event category associated with the life event type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\LifeEventCategory, $this>
     */
    public function lifeEventCategory(): BelongsTo
    {
        return $this->belongsTo(LifeEventCategory::class);
    }

    /**
     * Get the life events associated with the life event type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LifeEvent, $this>
     */
    public function lifeEvents(): HasMany
    {
        return $this->hasMany(LifeEvent::class);
    }

    /**
     * Get the label attribute.
     * Life Event categories have a default label that can be translated.
     * Howerer, if a label is set, it will be used instead of the default.
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
