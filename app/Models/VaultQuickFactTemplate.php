<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultQuickFactTemplate extends Model
{
    use HasFactory;

    protected $table = 'vault_quick_facts_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'vault_id',
        'label',
        'label_translation_key',
        'position',
    ];

    /**
     * Get the vault associated with the quick fact template.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the label attribute.
     * Vault quick fact template entries have a default label that can be translated.
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
