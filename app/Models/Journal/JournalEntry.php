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


namespace App\Models\Journal;

use App\Models\Contact\Entry;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property User $invitedBy
 */
class JournalEntry extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'journal_entries';

    protected $dates = [
        'date',
    ];

    /**
     * Get all of the owning "journal-able" models.
     */
    public function journalable()
    {
        return $this->morphTo();
    }

    /**
     * Get the account record associated with the invitation.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Adds a new entry in the journal.
     * @param mixed $resourceToLog
     */
    public function add($resourceToLog)
    {
        $this->account_id = $resourceToLog->account_id;
        $this->date = now();
        if ($resourceToLog instanceof \App\Models\Contact\Activity) {
            $this->date = $resourceToLog->date_it_happened;
        }
        if ($resourceToLog instanceof \App\Models\Journal\Entry) {
            $this->date = $resourceToLog->date;
        }
        $this->journalable_id = $resourceToLog->id;
        $this->journalable_type = get_class($resourceToLog);
        $this->save();

        return $this;
    }

    /**
     * Get the information about the object represented by the Journal Entry.
     * @return array
     */
    public function getObjectData()
    {
        $type = $this->journalable_type;

        // Instantiating the object
        $correspondingObject = (new $type)->findOrFail($this->journalable_id);

        return $correspondingObject->getInfoForJournalEntry();
    }
}
