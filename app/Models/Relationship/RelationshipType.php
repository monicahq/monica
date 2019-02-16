<?php

namespace App\Models\Relationship;

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
     * Get the i18n version of the name attribute, like "Significant other".
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     *
     * @return array|string|null|\Illuminate\Contracts\Translation\Translator
     */
    public function getLocalizedName(Contact $contact = null, bool $includeOpposite = false, string $gender = 'man')
    {
        // include the reverse of the relation in the string (masculine/feminine)
        // this is used in the dropdown of the relationship types when creating
        // or deleting a relationship.
        if (! is_null($contact) && $includeOpposite) {
            // in some language, masculine and feminine version of a relationship type is the same.
            // we need to keep just one version in that case.
            $femaleVersion = trans('app.relationship_type_'.$this->name.'_female');

            // `Regis Freyd's significant other`
            if (strtolower($femaleVersion) == strtolower($this->getLocalizedName())) {
                return trans('app.relationship_type_'.$this->name.'_with_name', ['name' => $contact->name]);
            }

            // otherwise `Regis Freyd's uncle/aunt`
            return trans(
                'app.relationship_type_'.$this->name.'_with_name',
                ['name' => $contact->name]
            ).'/'.$femaleVersion;
        }

        // `Regis Freyd's uncle`
        if (! is_null($contact)) {
            if (strtolower($gender) == 'woman') {
                return trans('app.relationship_type_'.$this->name.'_female_with_name', ['name' => $contact->name]);
            }

            return trans('app.relationship_type_'.$this->name.'_with_name', ['name' => $contact->name]);
        }

        // `aunt`
        if (strtolower($gender) == 'woman') {
            return trans('app.relationship_type_'.$this->name.'_female');
        }

        // `uncle`
        return trans('app.relationship_type_'.$this->name);
    }
}
