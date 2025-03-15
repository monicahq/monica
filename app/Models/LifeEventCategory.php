<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeEventCategory extends Model
{
    use HasFactory;

    protected $table = 'life_event_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'label',
        'label_translation_key',
        'can_be_deleted',
        'position',
    ];

    /**
     * Get the vault associated with the life event category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the life event types associated with the life event category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LifeEventType, $this>
     */
    public function lifeEventTypes(): HasMany
    {
        return $this->hasMany(LifeEventType::class);
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
