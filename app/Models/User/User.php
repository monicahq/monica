<?php

namespace App\Models\User;

use Carbon\Carbon;
use App\Helpers\DateHelper;
use App\Models\Journal\Day;
use App\Models\Settings\Term;
use App\Helpers\RequestHelper;
use App\Models\Account\Account;
use App\Helpers\CountriesHelper;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use App\Notifications\ConfirmEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Account\User\User as UserResource;
use App\Http\Resources\Settings\Compliance\Compliance as ComplianceResource;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'timezone',
        'locale',
        'currency_id',
        'fluid_container',
        'temperature_scale',
        'name_order',
        'google2fa_secret',
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'profile_new_life_event_badge_seen' => 'boolean',
    ];

    /**
     * Create a new User.
     *
     * @param int $account_id
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     * @param string $password
     * @param string $ipAddress
     * @param string $lang
     * @return $this
     */
    public static function createDefault($account_id, $first_name, $last_name, $email, $password, $ipAddress = null, $lang = null)
    {
        // create the user
        $user = new self;
        $user->account_id = $account_id;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        $user->password = bcrypt($password);
        $user->created_at = now();
        $user->locale = $lang ?: App::getLocale();

        $user->setDefaultCurrencyAndTimezone($ipAddress, $user->locale);

        $user->save();

        $user->acceptPolicy($ipAddress);

        return $user;
    }

    private function setDefaultCurrencyAndTimezone($ipAddress, $locale)
    {
        $infos = RequestHelper::infos($ipAddress);

        // Associate timezone and currency
        $currencyCode = $infos['currency'];
        $timezone = $infos['timezone'];
        if ($infos['country']) {
            $country = CountriesHelper::getCountry($infos['country']);
        } else {
            $country = CountriesHelper::getCountryFromLocale($locale);
        }

        // Timezone
        if (! is_null($timezone)) {
            $this->timezone = $timezone;
        } elseif (! is_null($country)) {
            $this->timezone = CountriesHelper::getDefaultTimezone($country);
        } else {
            $this->timezone = config('app.timezone');
        }

        // Currency
        if (! is_null($currencyCode)) {
            $this->associateCurrency($currencyCode);
        } elseif (! is_null($country)) {
            foreach ($country->currencies as $currency) {
                if ($this->associateCurrency($currency)) {
                    break;
                }
            }
        }

        // Temperature scale
        switch ($country->cca2) {
            case 'US':
            case 'BZ':
            case 'KY':
                $this->temperature_scale = 'fahrenheit';
                break;
            default:
                $this->temperature_scale = 'celsius';
                break;
        }
    }

    private function associateCurrency($currency) : bool
    {
        $currencyObj = Currency::where('iso', $currency)->first();
        if (! is_null($currencyObj)) {
            $this->currency()->associate($currencyObj);

            return true;
        }

        return false;
    }

    /**
     * Get the account record associated with the user.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the term records associated with the user.
     */
    public function terms()
    {
        return $this->belongsToMany(Term::class)->withPivot('ip_address')->withTimestamps();
    }

    /**
     * Get the recovery codes associated with the user.
     */
    public function recoveryCodes()
    {
        return $this->hasMany(RecoveryCode::class);
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

        if ($this->name_order == 'firstname_lastname' || $this->name_order == 'firstname_lastname_nickname') {
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
                ->where('date', now($this->timezone)->toDateString())
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
     * @param  string|null  $value
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
     * Indicate whether the user should be reminded at this time.
     * This is affected by the user settings regarding the hour of the day he
     * wants to be reminded.
     *
     * @param Carbon $date
     * @return bool
     */
    public function isTheRightTimeToBeReminded(Carbon $date)
    {
        $isTheRightTime = true;

        $dateToCompareTo = $date->hour(0)->minute(0)->second(0)->toDateString();
        $currentHourOnUserTimezone = now($this->timezone)->format('H:00');
        $currentDateOnUserTimezone = now($this->timezone)->hour(0)->minute(0)->second(0)->toDateString();
        $defaultHourReminderShouldBeSent = $this->account->default_time_reminder_is_sent;

        if ($dateToCompareTo != $currentDateOnUserTimezone) {
            $isTheRightTime = false;
        }

        if ($defaultHourReminderShouldBeSent != $currentHourOnUserTimezone) {
            $isTheRightTime = false;
        }

        return $isTheRightTime;
    }

    /**
     * Indicate if the user has accepted the most current terms and privacy.
     *
     * @return bool
     */
    public function isPolicyCompliant(): bool
    {
        $latestTerm = Term::latest()->first();

        if ($this->getStatusForCompliance($latestTerm->id) == false) {
            return false;
        }

        return true;
    }

    /**
     * Accept latest policy.
     *
     * @return Term|bool
     */
    public function acceptPolicy($ipAddress = null)
    {
        $latestTerm = Term::latest()->first();

        if (! $latestTerm) {
            return false;
        }

        $this->terms()->syncWithoutDetaching([$latestTerm->id => [
            'account_id' => $this->account_id,
            'ip_address' => $ipAddress,
        ]]);

        return $latestTerm;
    }

    /**
     * Get the status for a given term.
     *
     * @param int $termId
     * @return array|bool
     */
    public function getStatusForCompliance($termId)
    {
        // @TODO: use eloquent to do this instead
        $termUser = DB::table('term_user')->where('user_id', $this->id)
                                            ->where('account_id', $this->account_id)
                                            ->where('term_id', $termId)
                                            ->first();

        if (! $termUser) {
            return false;
        }

        $compliance = Term::find($termId);
        $signedDate = DateHelper::parseDateTime($termUser->created_at);

        return [
            'signed' => true,
            'signed_date' => DateHelper::getTimestamp($signedDate),
            'ip_address' => $termUser->ip_address,
            'user' => new UserResource($this),
            'term' => new ComplianceResource($compliance),
        ];
    }

    /**
     * Get the list of all the policies the user has signed.
     *
     * @return array
     */
    public function getAllCompliances()
    {
        $terms = collect();
        $termsUser = DB::table('term_user')->where('user_id', $this->id)
                                                        ->get();

        foreach ($termsUser as $termUser) {
            $terms->push([
                $this->getStatusForCompliance($termUser->term_id),
            ]);
        }

        return $terms;
    }

    /**
     * Get the name order that will be used when rendered the Add/Edit forms
     * about contacts.
     *
     * @return string
     */
    public function getNameOrderForForms(): string
    {
        $nameOrder = '';

        switch ($this->name_order) {
            case 'firstname_lastname':
            case 'firstname_lastname_nickname':
            case 'firstname_nickname_lastname':
            case 'nickname':
                $nameOrder = 'firstname';
                break;
            case 'lastname_firstname':
            case 'lastname_firstname_nickname':
            case 'lastname_nickname_firstname':
                $nameOrder = 'lastname';
                break;
        }

        return $nameOrder;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new ConfirmEmail(true));
    }
}
