<?php

namespace App;

use App\Gift;
use App\User;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;

class Kid extends Model
{
    protected $dates = ['birthdate'];

    /**
     * Gets the age of the kid in years, or returns null if the birthdate
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
     * Returns the birthdate of the kid.
     *
     * @return string
     */
    public function getBirthdate()
    {
        if (is_null($this->birthdate)) {
            return Carbon::now()->format('Y-m-d');
        }

        return $this->birthdate;
    }

    /**
     * Return the first name of the kid.
     * @return string
     */
    public function getFirstName()
    {
        if (is_null($this->first_name)) {
            return null;
        }

        return $this->first_name;
    }
}
