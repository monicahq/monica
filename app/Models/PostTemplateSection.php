<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTemplateSection extends Model
{
    use HasFactory;

    protected $table = 'post_template_sections';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_template_id',
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
     * Get the post type associated with the post type section.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\PostTemplate, $this>
     */
    public function postTemplate(): BelongsTo
    {
        return $this->belongsTo(PostTemplate::class);
    }

    /**
     * Post template section entries have a default name that can be translated.
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
