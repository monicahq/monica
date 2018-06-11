<?php

namespace App\Models\Relationship;

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;

/**
 * A relationship defines relations between contacts.
 */
class Relationship extends Model
{
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

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function contactIs()
    {
        return $this->belongsTo(Contact::class, 'contact_is');
    }

    public function ofContact()
    {
        return $this->belongsTo(Contact::class, 'of_contact');
    }

    public function relationshipType()
    {
        return $this->belongsTo(RelationshipType::class, 'relationship_type_id');
    }
}
