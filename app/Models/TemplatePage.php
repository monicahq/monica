<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TemplatePage extends Model
{
    use HasFactory;

    protected $table = 'template_pages';

    /**
     * Possible template page types.
     */
    public const TYPE_CONTACT = 'contact_information';

    public const TYPE_FEED = 'feed';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'template_id',
        'name',
        'name_translation_key',
        'position',
        'slug',
        'can_be_deleted',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the account associated with the template page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the modules associated with the template page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Module, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_template_page')
            ->withTimestamps();
    }

    /**
     * Get the name attribute.
     * Template pages have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return __($attributes['name_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
