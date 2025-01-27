<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    /**
     * Possible module types.
     */
    public const TYPE_NOTES = 'notes';

    public const TYPE_CONTACT_NAMES = 'contact_names';

    public const TYPE_AVATAR = 'avatar';

    public const TYPE_FAMILY_SUMMARY = 'family_summary';

    public const TYPE_COMPANY = 'company';

    public const TYPE_FEED = 'feed';

    public const TYPE_GENDER_PRONOUN = 'gender_pronoun';

    public const TYPE_IMPORTANT_DATES = 'important_dates';

    public const TYPE_LABELS = 'labels';

    public const TYPE_REMINDERS = 'reminders';

    public const TYPE_LOANS = 'loans';

    public const TYPE_RELATIONSHIPS = 'relationships';

    public const TYPE_TASKS = 'tasks';

    public const TYPE_CALLS = 'calls';

    public const TYPE_PETS = 'pets';

    public const TYPE_GOALS = 'goals';

    public const TYPE_ADDRESSES = 'addresses';

    public const TYPE_GROUPS = 'groups';

    public const TYPE_CONTACT_INFORMATION = 'contact_information';

    public const TYPE_DOCUMENTS = 'documents';

    public const TYPE_PHOTOS = 'photos';

    public const TYPE_POSTS = 'posts';

    public const TYPE_RELIGIONS = 'religions';

    public const TYPE_LIFE_EVENTS = 'life_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'name_translation_key',
        'type',
        'can_be_deleted',
        'reserved_to_contact_information',
        'pagination',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
        'reserved_to_contact_information' => 'boolean',
    ];

    /**
     * Get the account associated with the template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the module rows associated with the module.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ModuleRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ModuleRow::class);
    }

    /**
     * Get the template pages associated with the module.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\TemplatePage, $this>
     */
    public function templatePages(): BelongsToMany
    {
        return $this->belongsToMany(TemplatePage::class, 'module_template_page')
            ->withTimestamps();
    }

    /**
     * Get the name attribute.
     * Modules have a default name that can be translated.
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
