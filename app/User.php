<?php

namespace App;

use App\Day;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class User extends Authenticatable
{
    use Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'timezone', 'locale', 'currency_id', 'fluid_container', 'name_order',
    ];

    /**
     * Eager load account with every user.
     */
    protected $with = ['account'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the account record associated with the user.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Assigns a default value just in case the sort order is empty.
     *
     * @param string $value
     * @return string
     */
    public function getContactsSortOrderAttribute($value)
    {
        return ! empty($value) ? $value : 'firstnameAZ';
    }

    /**
     * Indicates if the layout is fluid or not for the UI.
     * @return string
     */
    public function getFluidLayout()
    {
        if ($this->fluid_container == 'true') {
            return 'container-fluid';
        } else {
            return 'container';
        }
    }

    /**
     * @return string
     */
    public function getMetricSymbol()
    {
        if ($this->metric == 'fahrenheit') {
            return 'F';
        } else {
            return 'C';
        }
    }

    /**
     * Get users's full name. The name is formatted according to the user's
     * preference, either "Firstname Lastname", or "Lastname Firstname".
     *
     * @return string
     */
    public function getNameAttribute()
    {
        $completeName = '';

        if ($this->name_order == 'firstname_first') {
            $completeName = $this->first_name;

            if (! is_null($this->last_name)) {
                $completeName = $completeName.' '.$this->last_name;
            }
        } else {
            if (! is_null($this->last_name)) {
                $completeName = $this->last_name;
            }

            $completeName = $completeName.' '.$this->first_name;
        }

        return $completeName;
    }

    /**
     * Gets the currency for this user.
     *
     * @return BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Set the contact view preference.
     *
     * @param  string $preference
     */
    public function updateContactViewPreference($preference)
    {
        $this->contacts_sort_order = $preference;
        $this->save();
    }

    /**
     * Indicates whether the user has already rated the current day.
     * @return boolean
     */
    public function hasAlreadyRatedToday()
    {
        try {
            $day = Day::where('account_id', $this->account_id)
                ->where('date', \Carbon\Carbon::now($this->timezone)->format('Y-m-d'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return true;
    }
}
