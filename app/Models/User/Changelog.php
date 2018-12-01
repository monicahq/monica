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



namespace App\Models\User;

use Parsedown;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the user records associated with the tag.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('read', 'upvote')->withTimestamps();
    }

    /**
     * Return the markdown parsed description.
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return (new Parsedown())->text($value);
    }

    /**
     * Return the created_at date in a friendly format.
     *
     * @return string
     */
    public function getCreatedAtAttribute($value)
    {
        return DateHelper::getShortDate($value);
    }
}
