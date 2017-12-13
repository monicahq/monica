<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Day extends Model
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
}
