<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;

    /**
     * Possible module types.
     */
    const TYPE_NOTES = 'notes';
    const TYPE_CONTACT_NAMES = 'contact_names';
    const TYPE_AVATAR = 'avatar';
    const TYPE_FEED = 'feed';
    const TYPE_GENDER_PRONOUN = 'gender_pronoun';
    const TYPE_IMPORTANT_DATES = 'important_dates';
    const TYPE_LABELS = 'labels';
    const TYPE_REMINDERS = 'reminders';
    const TYPE_LOANS = 'loans';

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
