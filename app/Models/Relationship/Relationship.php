<?php

namespace App\Models\Relationship;

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
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
}
