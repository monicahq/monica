<?php

namespace App;

class ActivityStatistic extends BaseMigrationModel
{
    protected $table = 'activity_statistics';

    /**
     * Get the account record associated with the activity statistic.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contact record associated with the activity statistic.
     */
    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }
}
