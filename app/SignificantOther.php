<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SignificantOther extends Model
{
    protected $table = 'significant_others';

    protected $dates = [
        'birthdate',
    ];

    /**
     * Get the account record associated with the significant other.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contact record associated with the significant other.
     */
    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    /**
     * Limit the query to active significant others
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the first name of the significant other.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->first_name == '') {
            return null;
        }

        return $this->first_name;
    }

    /**
     * Get the birthdate of the contact.
     *
     * @return Carbon
     */
    public function getBirthdate()
    {
        if (is_null($this->birthdate)) {
            return null;
        }

        return $this->birthdate;
    }

    /**
     * Gets the age of the contact in years, or returns null if the birthdate
     * is not set.
     *
     * @return int
     */
    public function getAge()
    {
        if (is_null($this->birthdate)) {
            return null;
        }

        $age = $this->birthdate->diffInYears(Carbon::now());
        return $age;
    }

    /**
     * Returns 'true' if the birthdate is an approximation
     *
     * @return string
     */
    public function isBirthdateApproximate()
    {
        return $this->is_birthdate_approximate;
    }
}
