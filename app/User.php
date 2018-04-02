<?php

namespace App;

use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'timezone', 'locale', 'currency_id', 'fluid_container', 'name_order', 'google2fa_secret',
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
        'password', 'remember_token', 'google2fa_secret',
    ];

    /**
     * Create a new User.
     *
     * @param int $account_id
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     * @param string $password
     * @return this
     */
    public static function createDefault($account_id, $first_name, $last_name, $email, $password)
    {
        // create the user
        $user = new self;
        $user->account_id = $account_id;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        $user->password = bcrypt($password);
        $user->timezone = config('app.timezone');
        $user->created_at = now();
        $user->locale = \App::getLocale();
        $user->save();

        return $user;
    }

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
     * Get the user's locale.
     *
     * @param  string  $value
     * @return string
     */
    public function getLocaleAttribute($value)
    {
        return $value;
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
     * @return bool
     */
    public function hasAlreadyRatedToday()
    {
        try {
            Day::where('account_id', $this->account_id)
                ->where('date', now($this->timezone)->format('Y-m-d'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * Ecrypt the user's google_2fa secret.
     *
     * @param  string  $value
     * @return string
     */
    public function setGoogle2faSecretAttribute($value)
    {
        $this->attributes['google2fa_secret'] = encrypt($value);
    }

    /**
     * Decrypt the user's google_2fa secret.
     *
     * @param  string  $value
     * @return string|null
     */
    public function getGoogle2faSecretAttribute($value)
    {
        if (is_null($value)) {
            return $value;
        }

        return decrypt($value);
    }

    /**
     * Indicate whether the user should be reminded about a reminder or notification.
     * The user should be reminded only if the date of the reminder matches the
     * current date, and the current hour matches the hour the account owner
     * wants to be reminded.
     *
     * @param Carbon $date
     * @return bool
     */
    public function shouldBeReminded(Carbon $date)
    {
        $dateOfReminder = $date->hour(0)->minute(0)->second(0)->toDateString();

        $currentDate = now($this->timezone);

        $currentHourOnUserTimezone = $currentDate->format('H:00');
        $currentDateOnUserTimezone = $currentDate->hour(0)->minute(0)->second(0)->toDateString();

        $hourEmailShouldBeSent = $this->account->default_time_reminder_is_sent;

        if ($dateOfReminder != $currentDateOnUserTimezone) {
            return false;
        }

        if ($hourEmailShouldBeSent != $currentHourOnUserTimezone) {
            return false;
        }

        return true;
    }
}
