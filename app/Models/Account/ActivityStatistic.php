<?php

namespace App\Models\Account;

use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;

class ActivityStatistic extends Model
{
    protected $table = 'activity_statistics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'contact_id',
        'year',
        'count',
    ];

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
