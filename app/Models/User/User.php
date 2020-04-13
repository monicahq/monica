<?php

namespace App\Models\User;

use Carbon\Carbon;
use App\Models\Journal\Day;
use App\Models\Settings\Term;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Helpers\ComplianceHelper;
use App\Models\Settings\Currency;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail, HasLocalePreference
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
        'admin' => 'boolean',
    ];

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
     * Get the contact record associated with the 'me' contact.
     *
     * @return HasOne
     */
    public function me()
    {
        return $this->hasOne(Contact::class, 'id', 'me_contact_id');
    }

    /**
     * Get the term records associated with the user.
     *
     * @return BelongsToMany
     */
    public function terms()
    {
        return $this->belongsToMany(Term::class)->withPivot('ip_address')->withTimestamps();
    }

    /**
     * Get the recovery codes associated with the user.
     *
     * @return HasMany
     */
    public function recoveryCodes()
    {
        return $this->hasMany(RecoveryCode::class);
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
     * Assigns a default value just in case the sort order is empty.
     *
     * @param string $value
     * @return string
     */
    public function getContactsSortOrderAttribute($value): string
    {
        return ! empty($value) ? $value : 'firstnameAZ';
    }

    /**
     * Indicates if the layout is fluid or not for the UI.
     *
     * @return string
     */
    public function getFluidLayout(): string
    {
        if ($this->fluid_container == 'true') {
            return 'container-fluid';
        } else {
            return 'container';
        }
    }

    /**
     * Get users's full name. The name is formatted according to the user's
     * preference, either "Firstname Lastname", or "Lastname Firstname".
     *
     * @return string
     */
    public function getNameAttribute(): string
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
     * Ecrypt the user's google_2fa secret.
     *
     * @param string  $value
     * @return void
     */
    public function setGoogle2faSecretAttribute($value): void
    {
        $this->attributes['google2fa_secret'] = encrypt($value);
    }

    /**
     * Decrypt the user's google_2fa secret.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getGoogle2faSecretAttribute($value): ?string
    {
        return is_null($value) ? null : decrypt($value);
    }

    /**
     * Indicate if the user has accepted the most current terms and privacy.
     *
     * @param string|null $value
     * @return bool
     */
    public function getPolicyCompliantAttribute($value): bool
    {
        return ComplianceHelper::isCompliantWithCurrentTerm($this);
    }

    /**
     * Indicate whether the user should be reminded at this time.
     * This is affected by the user settings regarding the hour of the day he
     * wants to be reminded.
     *
     * @param Carbon|null $date
     * @return bool
     */
    public function isTheRightTimeToBeReminded($date)
    {
        if (is_null($date)) {
            return false;
        }

        $isTheRightTime = true;

        // compare date with current date for the user
        if (! $date->isSameDay(now($this->timezone))) {
            $isTheRightTime = false;
        }

        // compare current hour for the user with the hour they want to be
        // reminded as per the hour set on the profile
        $currentHourOnUserTimezone = now($this->timezone)->format('H:00');
        $defaultHourReminderShouldBeSent = $this->account->default_time_reminder_is_sent;

        if ($defaultHourReminderShouldBeSent != $currentHourOnUserTimezone) {
            $isTheRightTime = false;
        }

        return $isTheRightTime;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        /** @var int $count */
        $count = Account::count();
        if (config('monica.signup_double_optin') && $count > 1) {
            $this->notify(new VerifyEmail());
        }
    }

    /**
     * Get the preferred locale of the entity.
     *
     * @return string|null
     */
    public function preferredLocale()
    {
        return $this->locale;
    }
}
