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


namespace App\Http\Resources\Contact;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class ContactShort extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'contact',
            'hash_id' => $this->is_partial ? $this->getRelatedRealContact()->hashID() : $this->hashID(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'nickname' => $this->nickname,
            'complete_name' => $this->name,
            'initials' => $this->getInitials(),
            'gender' => $this->gender->name,
            'is_partial' => (bool) $this->is_partial,
            'is_dead' => (bool) $this->is_dead,
            'information' => [
                'birthdate' => [
                    'is_age_based' => (is_null($this->birthdate) ? null : (bool) $this->birthdate->is_age_based),
                    'is_year_unknown' => (is_null($this->birthdate) ? null : (bool) $this->birthdate->is_year_unknown),
                    'date' => DateHelper::getTimestamp($this->birthdate),
                ],
                'deceased_date' => [
                    'is_age_based' => (is_null($this->deceasedDate) ? null : (bool) $this->deceasedDate->is_age_based),
                    'is_year_unknown' => (is_null($this->deceasedDate) ? null : (bool) $this->deceasedDate->is_year_unknown),
                    'date' => DateHelper::getTimestamp($this->deceasedDate),
                ],
                'avatar' => [
                    'has_avatar' => $this->has_avatar,
                    'avatar_url' => $this->getAvatarURL(110),
                    'default_avatar_color' => $this->default_avatar_color,
                ],
            ],
            'account' => [
                'id' => $this->account->id,
            ],
        ];
    }
}
