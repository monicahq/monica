<?php

namespace App;

use App\Gift;
use App\User;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $parent
 * @property Reminder $reminder
 */
class Kid extends Model
{
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
    protected $dates = ['birthdate'];

    /**
     * Get the account record associated with the kid.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the kid.
     *
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Contact::class, 'child_of_contact_id');
    }

    /**
     * Get the reminder record associated with the significant other.
     *
     * @return BelongsTo
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class, 'birthday_reminder_id');
    }

    /**
     * Assigns a birthday or birth year to the loved one based on
     * the data  provided.
     *
     * @param string $approximation ['unknown', 'exact', 'approximate']
     * @param \DateTime|string $exactDate
     * @param string|int $age
     * @return static
     */
    public function assignBirthday($approximation, $exactDate, $age = null)
    {
        if ($approximation === 'approximate') {
            $this->birthdate = Carbon::now()->subYears($age)->month(1)->day(1);
        } elseif ($approximation === 'exact') {
            $this->birthdate = Carbon::parse($exactDate);
        } else {
            $this->birthdate = null;
        }

        $this->save();

        return $this;
    }

    /**
     * Get the date_it_happened field according to user's timezone
     *
     * @param string $value
     * @return string
     */
    public function getBirthdateAttribute($value)
    {
        if (auth()->user()) {
            return Carbon::parse($value, auth()->user()->timezone);
        }

        return Carbon::parse($value);
    }

    /**
     * Get age according to the birthdate
     *
     * @return string
     */
    public function getAgeAttribute()
    {
        return ! $this->birthdate->isToday() ? $this->birthdate->diffInYears(Carbon::now()) : null;
    }

    /**
     * Gets the age of the kid in years, or returns null if the birthdate
     * is not set.
     *
     * @return int
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Returns the birthdate of the kid.
     *
     * @return string
     */
    public function getBirthdate()
    {
        if (is_null($this->birthdate)) {
            return null;
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
