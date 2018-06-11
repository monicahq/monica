<?php

namespace App\Models\Journal;

use App\Helpers\DateHelper;
use App\Traits\Journalable;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Model;
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
