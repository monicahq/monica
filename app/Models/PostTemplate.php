<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostTemplate extends Model
{
    use HasFactory;

    protected $table = 'post_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'label',
        'label_translation_key',
        'position',
        'can_be_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the account associated with the post type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the post type section records associated with the post type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\PostTemplateSection, $this>
     */
    public function postTemplateSections(): HasMany
    {
        return $this->hasMany(PostTemplateSection::class);
    }

    /**
     * Get the post template attribute.
     * Post template entries have a default name that can be translated.
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
