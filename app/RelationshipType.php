<?php

namespace App;

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
     * Get the account record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function relationshipTypeGroup()
    {
        return $this->belongsTo('App\RelationshipTypeGroup');
    }

    /**
     * Get the i18n version of the name attribute, like "Significant other".
     *
     * @return string
     */
    public function getLocalizedName(Contact $contact = null, bool $includeReverse = false)
    {
        // include the reverse of the relation in the string (masculine/feminine)
        if (! is_null($contact) && $includeReverse) {
            // in some language, masculine and feminine version of a relationship type is the same.
            // we need to keep just one version in that case.
            $femaleVersion = trans('app.relationship_type_'.$this->name.'_female');

            // `Regis Freyd's significant other`
            if (strtolower($femaleVersion) == strtolower($this->getLocalizedName())) {
                return trans('app.relationship_type_'.$this->name.'_with_name', ["name" => $contact->getCompleteName()]);
            }

            // `Regis Freyd's uncle/aunt`
            return trans(
                'app.relationship_type_'.$this->name.'_with_name',
                ["name" => $contact->getCompleteName()]
            ).'/'.$femaleVersion;
        }

        // `Regis Freyd's uncle`
        if (! is_null($contact)) {
            return trans('app.relationship_type_'.$this->name.'_with_name', ["name" => $contact->getCompleteName()]);
        }

        // `uncle`
        return trans('app.relationship_type_'.$this->name);
    }

    public function getReverse()
    {
        return $this->account->getRelationshipTypeByType($this->name);
    }
}
