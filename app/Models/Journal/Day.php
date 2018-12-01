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

use App\Helpers\DateHelper;
use App\Traits\Journalable;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use App\Interfaces\IsJournalableInterface;

class Day extends Model implements IsJournalableInterface
{
    use Journalable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $dates = [
        'date',
    ];

    /**
     * Get the account record associated with the debt.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the day's rate.
     *
     * @param  int  $value
     * @return int
     */
    public function getRateAttribute($value)
    {
        return $value;
    }

    /**
     * Get the day's comment.
     *
     * @param  string  $value
     * @return string
     */
    public function getCommentAttribute($value)
    {
        return $value;
    }

    /**
     * Get all the information of the Entry for the journal.
     * @return array
     */
    public function getInfoForJournalEntry()
    {
        return [
            'type' => 'day',
            'id' => $this->id,
            'rate' => $this->rate,
            'comment' => $this->comment,
            'day' => $this->date->day,
            'day_name' => mb_convert_case(DateHelper::getShortDay($this->date), MB_CASE_TITLE, 'UTF-8'),
            'month' => $this->date->month,
            'month_name' => mb_convert_case(DateHelper::getShortMonth($this->date), MB_CASE_UPPER, 'UTF-8'),
            'year' => $this->date->year,
            'happens_today' => $this->date->isToday(),
        ];
    }
}
