<?php

namespace App\Models;

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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'name',
        'type',
        'can_be_deleted',
        'reserved_to_contact_information',
        'pagination',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
        'reserved_to_contact_information' => 'boolean',
    ];

    /**
     * Get the account associated with the template.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the module rows associated with the module.
     *
     * @return HasMany
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ModuleRow::class);
    }

    /**
     * Get the template pages associated with the module.
     *
     * @return BelongsToMany
     */
    public function templatePages(): BelongsToMany
    {
        return $this->belongsToMany(TemplatePage::class, 'module_template_page')->withTimestamps();
    }
}
