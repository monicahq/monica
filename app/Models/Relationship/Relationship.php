<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
