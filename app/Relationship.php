<?php

namespace App;

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
        'contact_id',
        'with_contact_id',
        'is_active',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id_main');
    }

    public function withContact()
    {
        return $this->belongsTo(Contact::class, 'contact_id_secondary');
    }

    public function relationshipType()
    {
        return $this->belongsTo('App\RelationshipType', 'relationship_type_id');
    }
}
