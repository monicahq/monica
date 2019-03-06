<?php

namespace App\Models\Instance;

use Carbon\Carbon;
use App\Helpers\DateHelper;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A special date is a date that is not necessarily based on a year that we know.
 * This happens when we add a birthdate for instance. It can be based:
 *     * on a real date, where we know the day, month and year
 *     * on a date where we just know the day and the month but not the year
 *     * on an age (we know this person is 33 but we don't know his birthdate,
 *       so we'll give an estimation).
 *
 * Instead of adding a lot of logic in the Contact table, we've decided to
 * create this class that will deal with this complexity.
 */
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
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['contact'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'account_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_age_based' => 'boolean',
        'is_year_unknown' => 'boolean',
    ];

    /**
     * Get the account record associated with the special date.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the special date.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Returns a short version of the date, taking into account if the year is
     * unknown or not. This will return either `July 21` or `July 21, 2017`.
     */
    public function toShortString()
    {
        if ($this->is_year_unknown) {
            return DateHelper::getShortDateWithoutYear($this->date);
        }

        return DateHelper::getShortDate($this->date);
    }

    /**
     * Returns the age that the date represents, if the date is set and if it's
     * not based on a year we don't know.
     * @return int|null
     */
    public function getAge()
    {
        if (is_null($this->date)) {
            return;
        }

        if ($this->is_year_unknown) {
            return;
        }

        return $this->date->diffInYears(now());
    }

    /**
     * Create a SpecialDate from an age.
     * @param  int $age
     */
    public function createFromAge(int $age)
    {
        $this->is_age_based = true;
        $this->date = now()->subYears($age)->month(1)->day(1);
        $this->save();

        return $this;
    }

    /**
     * Create a SpecialDate from an actual date, that might not contain a year.
     * @param  int    $year
     * @param  int    $month
     * @param  int    $day
     */
    public function createFromDate(int $year, int $month, int $day)
    {
        // year 0 represents the `unknown` choice in the dropdown representing
        // the years
        if ($year != 0) {
            $date = Carbon::createFromDate($year, $month, $day);
        } else {
            $date = Carbon::createFromDate(now()->year, $month, $day);
            $this->is_year_unknown = true;
        }

        $this->date = $date;
        $this->save();

        return $this;
    }

    /**
     * Associates a special date to a contact.
     * @param Contact $contact
     */
    public function setToContact(Contact $contact)
    {
        $this->account_id = $contact->account_id;
        $this->contact_id = $contact->id;
        $this->save();

        return $this;
    }
}
