<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use LaravelWebauthn\WebauthnAuthenticatable;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use WebauthnAuthenticatable;

    /**
     * Possible number format types.
     */
    /** Locale default */
    public const NUMBER_FORMAT_TYPE_LOCALE_DEFAULT = 'locale';

    /** English version */
    public const NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL = '1,234.56';

    /** French version */
    public const NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL = '1 234,56';

    /** German version */
    public const NUMBER_FORMAT_TYPE_DOT_THOUSANDS_COMMA_DECIMAL = '1.234,56';

    /** Exchange version */
    public const NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL = '1234.56';

    public const NUMBER_FORMAT_TYPES = [
        self::NUMBER_FORMAT_TYPE_LOCALE_DEFAULT,
        self::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        self::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
        self::NUMBER_FORMAT_TYPE_DOT_THOUSANDS_COMMA_DECIMAL,
        self::NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL,
    ];

    /**
     * Possible maps site.
     */
    public const MAPS_SITE_GOOGLE_MAPS = 'google_maps';

    public const MAPS_SITE_OPEN_STREET_MAPS = 'open_street_maps';

    /**
     * Possible distance unit.
     */
    public const DISTANCE_UNIT_MILES = 'mi';

    public const DISTANCE_UNIT_KM = 'km';

    /**
     * Possible contact sort order.
     */
    public const CONTACT_SORT_ORDER_ASC = 'asc';

    public const CONTACT_SORT_ORDER_DESC = 'desc';

    public const CONTACT_SORT_ORDER_LAST_UPDATED = 'last_updated';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'is_account_administrator',
        'invitation_code',
        'invitation_accepted_at',
        'name_order',
        'date_format',
        'number_format',
        'distance_format',
        'timezone',
        'default_map_site',
        'locale',
        'help_shown',
        'contact_sort_order',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var list<string>
     */
    protected $visible = [
        'name',
        'first_name',
        'last_name',
        'email',
        'help_shown',
        'locale',
        'locale_ietf',
        'is_account_administrator',
        'timezone',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'name',
        'locale_ietf',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'is_account_administrator' => 'boolean',
        'is_instance_administrator' => 'boolean',
        'help_shown' => 'boolean',
    ];

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (config('mail.default') !== 'smtp' || (
            config('mail.mailers.smtp.username') !== null && config('mail.mailers.smtp.password') !== null
        )) {
            parent::sendEmailVerificationNotification();
        } else {
            $this->markEmailAsVerified();
        }
    }

    /**
     * Get the account record associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault records associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Vault, $this>
     */
    public function vaults(): BelongsToMany
    {
        return $this->belongsToMany(Vault::class)
            ->withPivot('permission', 'contact_id')
            ->withTimestamps();
    }

    /**
     * Get the contact records associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_vault_user')
            ->withPivot('is_favorite')
            ->withTimestamps();
    }

    /**
     * Get the note records associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'author_id');
    }

    /**
     * Get the notification channel records associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\UserNotificationChannel, $this>
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(UserNotificationChannel::class);
    }

    /**
     * Get the task records associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContactTask, $this>
     */
    public function contactTasks(): HasMany
    {
        return $this->hasMany(ContactTask::class, 'author_id');
    }

    /**
     * Get the name of the user.
     *
     * @return Attribute<string,never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $attributes['first_name'].' '.$attributes['last_name'];
            }
        );
    }

    /**
     * Get the locale of the user in the IETF format.
     *
     * @return Attribute<string,never>
     */
    protected function localeIetf(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return isset($attributes['locale']) ? Str::replace('_', '-', $attributes['locale']) : null;
            }
        );
    }

    /**
     * Get the contact of the user in the given vault.
     * All users have a contact in the vaults.
     */
    public function getContactInVault(Vault $vault): ?Contact
    {
        $entry = $this->vaults()
            ->wherePivot('vault_id', $vault->id)
            ->first();

        if ($entry === null) {
            return null;
        }

        try {
            return Contact::findOrFail($entry->pivot->contact_id);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * Get the user tokens for external login providers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\UserToken, $this>
     */
    public function userTokens()
    {
        return $this->hasMany(UserToken::class);
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
