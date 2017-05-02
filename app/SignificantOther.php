<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Events\SignificantOther\SignificantOtherCreated;
use App\Events\SignificantOther\SignificantOtherDeleted;
use App\Events\SignificantOther\SignificantOtherUpdated;

class SignificantOther extends Model
{
    protected $table = 'significant_others';

    protected $dates = [
        'birthdate',
    ];

    protected $events = [
        'created' => SignificantOtherCreated::class,
        'updated' => SignificantOtherUpdated::class,
        'deleted' => SignificantOtherDeleted::class,
    ];

    /**
     * Get the first name of the significant other.
     *
     * @return string
     */
    public function getFirstName()
    {
        if ($this->first_name == '') {
            return null;
        }

        return $this->first_name;
    }

    /**
     * Get the last name of the significant other.
     *
     * @return string
     */
    public function getLastName()
    {
        if ($this->last_name == '') {
            return null;
        }

        return $this->last_name;
    }

    /**
     * Get the complete name of the significant other.
     *
     * @return string
     */
    public function getCompleteName()
    {
        $completeName = $this->getFirstName();

        if (! is_null($this->getLastName())) {
            $completeName = $completeName.' '.$this->getLastName();
        }

        return $completeName;
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
