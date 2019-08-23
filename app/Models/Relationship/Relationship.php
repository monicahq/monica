<?php

namespace App\Models\Relationship;

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A relationship defines relations between contacts.
 */
class Relationship extends Model
{
    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = [
        'contactIs',
        'ofContact',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'contact_is',
        'of_contact',
        'relationship_type_id',
    ];

    /**
     * Get the account record associated with the relationship.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the relationship.
     *
     * @return BelongsTo
     */
    public function contactIs()
    {
        return $this->belongsTo(Contact::class, 'contact_is');
    }

    /**
     * Get the contact record connected with the relationship.
     *
     * @return BelongsTo
     */
    public function ofContact()
    {
        return $this->belongsTo(Contact::class, 'of_contact');
    }

    /**
     * Get the relationship type record associated with the relationship.
     *
     * @return BelongsTo
     */
    public function relationshipType()
    {
        return $this->belongsTo(RelationshipType::class, 'relationship_type_id');
    }

    /**
     * Get the reverser relationship of this one.
     *
     * @return self|null
     */
    public function reverseRelationship()
    {
        $reverseRelationshipType = $this->relationshipType->reverseRelationshipType();
        if ($reverseRelationshipType) {
            return self::where([
                'account_id'=> $this->account->id,
                'contact_is' => $this->of_contact,
                'of_contact' => $this->contact_is,
                'relationship_type_id' => $reverseRelationshipType->id,
            ])->first();
        }
    }
}
