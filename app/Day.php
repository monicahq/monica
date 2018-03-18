<?php

namespace App;

use App\Traits\Journalable;
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
     * Get all of the day's journal entries.
     */
    public function journalEntries()
    {
        return $this->morphMany('App\JournalEntry', 'journalable');
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
            'day_name' => ucfirst(\App\Helpers\DateHelper::getShortDay($this->date)),
            'month' => $this->date->month,
            'month_name' => strtoupper(\App\Helpers\DateHelper::getShortMonth($this->date)),
            'year' => $this->date->year,
            'happens_today' => $this->date->isToday(),
        ];
    }
}
