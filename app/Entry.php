<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
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
        'title',
        'post',
    ];

    /**
     * Get the account record associated with the entry.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    public function getPost()
    {
        if (is_null($this->post)) {
            return;
        }

        return $this->post;
    }

    public function getTitle()
    {
        if (is_null($this->title)) {
            return;
        }

        return $this->title;
    }

    public function getInfoForJournalEntry()
    {
        $data = [
            'type' => 'activity',
            'id' => $this->id,
            'title' => $this->title,
            'post' => $this->post,
            'day' => $this->created_at->day,
            'day_name' => \App\Helpers\DateHelper::getShortDay($this->created_at),
            'month' => $this->created_at->month,
            'month_name' => \App\Helpers\DateHelper::getShortMonth($this->created_at),
            'year' => $this->created_at->year,
        ];

        return $data;
    }
}
