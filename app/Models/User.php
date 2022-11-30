<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use LaravelWebauthn\WebauthnAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail, HasLocalePreference
{
    use Notifiable;
    use HasFactory;
    use HasApiTokens;
    use TwoFactorAuthenticatable;
    use WebauthnAuthenticatable;

    /**
     * Possible number format types.
     */
    public const NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL = '1,234.56';

    public const NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL = '1 234,56';

    public const NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL = '1234.56';

    /**
     * Possible maps site.
     */
    public const MAPS_SITE_GOOGLE_MAPS = 'google_maps';

    public const MAPS_SITE_OPEN_STREET_MAPS = 'open_street_maps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
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
        'timezone',
        'default_map_site',
        'locale',
        'help_shown',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'is_account_administrator' => 'boolean',
        'help_shown' => 'boolean',
    ];

    /**
     * Send the email verification notification.
     *
     * @return void
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
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault records associated with the user.
     *
     * @return BelongsToMany
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
     * @return BelongsToMany
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
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'author_id');
    }

    /**
     * Get the notification channel records associated with the user.
     *
     * @return HasMany
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(UserNotificationChannel::class, 'user_id');
    }

    /**
     * Get the task records associated with the user.
     *
     * @return HasMany
     */
    public function contactTasks(): HasMany
    {
        return $this->hasMany(ContactTask::class, 'author_id');
    }

    /**
     * Get the name of the user.
     *
     * @return Attribute
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
     * Get the contact of the user in the given vault.
     * All users have a contact in the vaults.
     *
     * @param  Vault  $vault
     * @return null|Contact
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
     * @return HasMany
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
