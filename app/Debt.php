<?php

namespace App;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Debt extends Model
{
    /**
     * Get the account record associated with the debt.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contact record associated with the debt.
     */
    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'inprogress');
    }

    public function scopeDue(Builder $query)
    {
        return $query->where('in_debt', 'yes');
    }

    public function scopeOwed(Builder $query)
    {
        return $query->where('in_debt', 'no');
    }
}
