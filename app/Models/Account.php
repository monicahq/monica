<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'storage_limit_in_mb',
    ];

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
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get the modules associated with the account.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get the information associated with the account.
     */
    public function groupTypes(): HasMany
    {
        return $this->hasMany(GroupType::class);
    }

    /**
     * Get the relationship group types associated with the account.
     */
    public function relationshipGroupTypes(): HasMany
    {
        return $this->hasMany(RelationshipGroupType::class);
    }

    /**
     * Get the genders associated with the account.
     */
    public function genders(): HasMany
    {
        return $this->hasMany(Gender::class);
    }

    /**
     * Get the pronouns associated with the account.
     */
    public function pronouns(): HasMany
    {
        return $this->hasMany(Pronoun::class);
    }

    /**
     * Get the contact information types associated with the account.
     */
    public function contactInformationTypes(): HasMany
    {
        return $this->hasMany(ContactInformationType::class);
    }

    /**
     * Get the address types associated with the account.
     */
    public function addressTypes(): HasMany
    {
        return $this->hasMany(AddressType::class);
    }

    /**
     * Get the pet categories associated with the account.
     */
    public function petCategories(): HasMany
    {
        return $this->hasMany(PetCategory::class);
    }

    /**
     * Get the emotions associated with the account.
     */
    public function emotions(): HasMany
    {
        return $this->hasMany(Emotion::class);
    }

    /**
     * Get the currencies in the account.
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class)
            ->withPivot('active')
            ->withTimestamps();
    }

    /**
     * Get the call reason types associated with the account.
     */
    public function callReasonTypes(): HasMany
    {
        return $this->hasMany(CallReasonType::class);
    }

    /**
     * Get the gift occasions associated with the account.
     */
    public function giftOccasions(): HasMany
    {
        return $this->hasMany(GiftOccasion::class);
    }

    /**
     * Get the gift stages associated with the account.
     */
    public function giftStates(): HasMany
    {
        return $this->hasMany(GiftState::class);
    }

    /**
     * Get the vaults associated with the account.
     */
    public function vaults(): HasMany
    {
        return $this->hasMany(Vault::class);
    }

    /**
     * Get the post templates associated with the account.
     */
    public function postTemplates(): HasMany
    {
        return $this->hasMany(PostTemplate::class);
    }

    /**
     * Get the religions associated with the account.
     */
    public function religions(): HasMany
    {
        return $this->hasMany(Religion::class);
    }
}
