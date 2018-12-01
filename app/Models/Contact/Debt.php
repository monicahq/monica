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
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingHasherWithContact as Model;

/**
 * @property Account $account
 * @property Contact $contact
 * @method static Builder due()s
 * @method static Builder owed()
 * @method static Builder inProgress()
 */
class Debt extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the debt.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the debt.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Limit results to unpaid/unreceived debt.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'inprogress');
    }

    /**
     * Limit results to due debt.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDue(Builder $query)
    {
        return $query->where('in_debt', 'yes');
    }

    /**
     * Limit results to owed debt.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOwed(Builder $query)
    {
        return $query->where('in_debt', 'no');
    }
}
