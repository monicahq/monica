<?php

namespace App\Models\Journal;

use Parsedown;
use App\Helpers\DateHelper;
use App\Traits\Journalable;
use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use App\Interfaces\IsJournalableInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model implements IsJournalableInterface
{
    use Journalable;

    protected $table = 'entries';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'title',
        'post',
    ];

    /**
     * Get the account record associated with the entry.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the Entry date.
     *
     * @param  string  $value
     * @return \Carbon\Carbon
     */
    public function getDateAttribute($value)
    {
        // Default to created_at, but show journalEntry->date if the entry type is JournalEntry
        return $this->journalEntry ? $this->journalEntry->date : $this->created_at;
    }

    /**
     * Get the Entry post.
     *
     * @param  string  $value
     * @return string
     */
    public function getPostAttribute($value)
    {
        return (new Parsedown())->text($value);
    }

    /**
     * Get all the information of the Entry for the journal.
     * @return array
     */
    public function getInfoForJournalEntry()
    {
        return [
            'type' => 'entry',
            'id' => $this->id,
            'title' => $this->title,
            'post' => $this->post,
            'day' => $this->date->day,
            'day_name' => mb_convert_case(DateHelper::getShortDay($this->date), MB_CASE_TITLE, 'UTF-8'),
            'month' => $this->date->month,
            'month_name' => mb_convert_case(DateHelper::getShortMonth($this->date), MB_CASE_UPPER, 'UTF-8'),
            'year' => $this->date->year,
            'date' => $this->date,
        ];
    }
}
