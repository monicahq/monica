<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialDate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'special_dates';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date'];

    /**
     * Get the account record associated with the gift.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    public function setAnnualReminder()
    {

    }

    public function deleteAnnualReminder()
    {

    }

    public function getAge()
    {
        if (is_null($this->date)) {
            return;
        }

        return $this->date->diffInYears(Carbon::now());
    }
}
