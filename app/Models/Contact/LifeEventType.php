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
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeEventType extends Model
{
    protected $table = 'life_event_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'account_id',
        'life_event_category_id',
        'default_life_event_type_key',
    ];

    /**
     * Get the account record associated with the life event type.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the life event category record associated with the life event type.
     */
    public function lifeEventCategory()
    {
        return $this->belongsTo(LifeEventCategory::class, 'life_event_category_id');
    }

    /**
     * Get the Life event records associated with the life event Type.
     *
     * @return HasMany
     */
    public function lifeEvents()
    {
        return $this->hasMany(LifeEvent::class);
    }

    /**
     * Get the life event type's attribute.
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        return $value;
    }
}
