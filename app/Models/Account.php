<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get the information associated with the account.
     *
     * Note to future reader of this code: i KNOW that information doesn't take
     * an "S" at the end, but I needed to indicate that there were many pieces
     * of information in a simple word. Deal with it.
     *
     * @return HasMany
     */
    public function informations()
    {
        return $this->hasMany(Information::class);
    }

    /**
     * Get the information associated with the account.
     *
     * @return HasMany
     */
    public function groupTypes()
    {
        return $this->hasMany(GroupType::class);
    }

    /**
     * Get the relationship group types associated with the account.
     *
     * @return HasMany
     */
    public function relationshipGroupTypes()
    {
        return $this->hasMany(RelationshipGroupType::class);
    }

    /**
     * Get the genders associated with the account.
     *
     * @return HasMany
     */
    public function genders()
    {
        return $this->hasMany(Gender::class);
    }

    /**
     * Get the pronouns associated with the account.
     *
     * @return HasMany
     */
    public function pronouns()
    {
        return $this->hasMany(Pronoun::class);
    }

    /**
     * Get the labels associated with the account.
     *
     * @return HasMany
     */
    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    /**
     * Get the contact information types associated with the account.
     *
     * @return HasMany
     */
    public function contactInformationTypes()
    {
        return $this->hasMany(ContactInformationType::class);
    }

    /**
     * Get the address types associated with the account.
     *
     * @return HasMany
     */
    public function addressTypes()
    {
        return $this->hasMany(AddressType::class);
    }

    /**
     * Get the pet categories associated with the account.
     *
     * @return HasMany
     */
    public function petCategories()
    {
        return $this->hasMany(PetCategory::class);
    }
}
