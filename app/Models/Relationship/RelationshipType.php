<?php

namespace App\Models\Relationship;

use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipType extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'relationship_types';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'delible' => 'boolean',
    ];

    /**
     * Get the account record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the relationship type group record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function relationshipTypeGroup()
    {
        return $this->belongsTo(RelationshipTypeGroup::class);
    }

    /**
     * Get the reverser relationship type of this one.
     *
     * @return self
     */
    public function reverseRelationshipType()
    {
        return $this->account->getRelationshipTypeByType($this->name_reverse_relationship);
    }

    /**
     * Get the i18n version of the name attribute, like "Significant other".
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     *
     * @param Contact $contact
     * @param bool $includeOpposite
     * @param string $gender
     * @return string|null|\Illuminate\Contracts\Translation\Translator
     */
    public function getLocalizedName(Contact $contact = null, bool $includeOpposite = false, string $gender = null)
    {
        $defaultGender = $this->account->defaultGender();
        if (is_null($gender)) {
            $gender = $defaultGender;
        }

        $femaleVersion = trans('app.relationship_type_'.$this->name.'_female');
        $maleVersion = trans('app.relationship_type_'.$this->name);

        if (! is_null($contact)) {
            $maleVersionWithName = trans('app.relationship_type_'.$this->name.'_with_name', ['name' => $contact->name]);
            $femaleVersionWithName = trans('app.relationship_type_'.$this->name.'_female_with_name', ['name' => $contact->name]);

            // include the reverse of the relation in the string (masculine/feminine)
            // this is used in the dropdown of the relationship types when creating
            // or deleting a relationship.
            if ($includeOpposite) {
                // in some language, masculine and feminine version of a relationship type is the same.
                // we need to keep just one version in that case.
                if ($femaleVersion === $maleVersion) {
                    // `Regis Freyd's significant other`
                    return $maleVersionWithName;
                }

                return $defaultGender === Gender::FEMALE ?
                    // `Regis Freyd's aunt/uncle`
                    $femaleVersionWithName.'/'.$maleVersion :
                    // `Regis Freyd's uncle/aunt`
                    $maleVersionWithName.'/'.$femaleVersion;
            } else {
                return $gender === Gender::FEMALE ?
                    // `Regis Freyd's aunt`
                    $femaleVersionWithName :
                    // `Regis Freyd's uncle`
                    $maleVersionWithName;
            }
        }

        return $gender === Gender::FEMALE ?
            // `aunt`
            $femaleVersion :
            // `uncle`
            $maleVersion;
    }
}
