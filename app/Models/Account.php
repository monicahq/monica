<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    /**
     * Get the users associated with the account.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the templates associated with the account.
     *
     * @return HasMany
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get the modules associated with the account.
     *
     * @return HasMany
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get the information associated with the account.
     *
     * @return HasMany
     */
    public function groupTypes(): HasMany
    {
        return $this->hasMany(GroupType::class);
    }

    /**
     * Get the relationship group types associated with the account.
     *
     * @return HasMany
     */
    public function relationshipGroupTypes(): HasMany
    {
        return $this->hasMany(RelationshipGroupType::class);
    }

    /**
     * Get the genders associated with the account.
     *
     * @return HasMany
     */
    public function genders(): HasMany
    {
        return $this->hasMany(Gender::class);
    }

    /**
     * Get the pronouns associated with the account.
     *
     * @return HasMany
     */
    public function pronouns(): HasMany
    {
        return $this->hasMany(Pronoun::class);
    }

    /**
     * Get the contact information types associated with the account.
     *
     * @return HasMany
     */
    public function contactInformationTypes(): HasMany
    {
        return $this->hasMany(ContactInformationType::class);
    }

    /**
     * Get the address types associated with the account.
     *
     * @return HasMany
     */
    public function addressTypes(): HasMany
    {
        return $this->hasMany(AddressType::class);
    }

    /**
     * Get the pet categories associated with the account.
     *
     * @return HasMany
     */
    public function petCategories(): HasMany
    {
        return $this->hasMany(PetCategory::class);
    }

    /**
     * Get the emotions associated with the account.
     *
     * @return HasMany
     */
    public function emotions(): HasMany
    {
        return $this->hasMany(Emotion::class);
    }

    /**
     * Get the currencies in the account.
     *
     * @return BelongsToMany
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'account_currencies', 'account_id', 'currency_id')
            ->withPivot('active')
            ->withTimestamps();
    }

    /**
     * Get the call reason types associated with the account.
     *
     * @return HasMany
     */
    public function callReasonTypes(): HasMany
    {
        return $this->hasMany(CallReasonType::class);
    }

    /**
     * Get the activity types associated with the account.
     *
     * @return HasMany
     */
    public function activityTypes(): HasMany
    {
        return $this->hasMany(ActivityType::class);
    }

    /**
     * Get the life event categories associated with the account.
     *
     * @return HasMany
     */
    public function lifeEventCategories(): HasMany
    {
        return $this->hasMany(LifeEventCategory::class);
    }
}
