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



namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBindingHasher as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['written_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'written_by_me' => 'boolean',
    ];

    /**
     * Get the account record associated with the message.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the message.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the Conversation records associated with the message.
     *
     * @return BelongsTo
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the content of the message.
     *
     * @return string
     */
    public function getContentAttribute($value)
    {
        return $value;
    }
}
