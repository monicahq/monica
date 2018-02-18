<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderRule extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'reminder_rules';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the account record associated with the reminder.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the number_of_days_before field.
     *
     * @param int $value
     * @return int
     */
    public function getNumberOfDaysBeforeAttribute($value)
    {
        return $value;
    }

    /**
     * Set the number_of_days_before field.
     *
     * @param int $value
     */
    public function setNumberOfDaysBeforeAttribute($value)
    {
        $this->attributes['number_of_days_before'] = $value;
    }
}
