<?php

namespace App;

use App\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Interfaces\IsJournalableInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Day extends Model implements IsJournalableInterface
{
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

    public function deleteJournalEntry()
    {
        try {
            $journalEntry = JournalEntry::where('account_id', $this->account_id)
                ->where('journalable_id', $this->id)
                ->where('journalable_type', 'App\Day')
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        $journalEntry->delete();
    }

    public function getInfoForJournalEntry()
    {
        $data = [
            'type' => 'day',
            'id' => $this->id,
            'rate' => $this->rate,
            'comment' => $this->comment,
            'day' => $this->date->day,
            'day_name' => ucfirst(\App\Helpers\DateHelper::getShortDay($this->date)),
            'month' => $this->date->month,
            'month_name' => strtoupper(\App\Helpers\DateHelper::getShortMonth($this->date)),
            'year' => $this->date->year,
        ];

        return $data;
    }
}
