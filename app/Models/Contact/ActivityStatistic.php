<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Model;

class ActivityStatistic extends Model
{
    protected $table = 'activity_statistics';

    /**
     * Get the account record associated with the activity statistic.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the activity statistic.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
