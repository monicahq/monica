<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\SendEmailVerification;
use App\Contracts\CarriesOnAccountChannel;
use App\Enums\DateFormatEnum;
use App\Enums\NameOrderEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Notifications\PasswordResetRequested;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification as LaravelNotification;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Passport\Token;
use Laravel\Sanctum\HasApiTokens;
use Override;

/**
 * Class User
 *
 * The person who signs in. There is exactly one per account. The user carries
 * the credentials, and the preferences that decide how everything is rendered:
 * language, time zone, clock, name order.
 *
 * @property int $id
 * @property int $account_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $photo_path
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $password
 * @property CarbonImmutable|null $password_changed_at
 * @property bool $is_active
 * @property string|null $locale
 * @property string $timezone
 * @property TimeFormatEnum $time_format
 * @property DateFormatEnum|null $date_format
 * @property NameOrderEnum $name_order
 * @property NumberFormatEnum|null $number_format
 * @property string|null $contact_list_grouping
 * @property string|null $contact_list_sort
 * @property string|null $contact_list_direction
 * @property bool $uses_readable_addresses
 * @property bool $shows_vault_list
 * @property CarbonImmutable|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $two_factor_secret
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property int|null $two_factor_last_timestep
 * @property CarbonImmutable|null $last_saved_at
 * @property CarbonImmutable|null $welcomed_at
 * @property CarbonImmutable|null $full_access_offer_dismissed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Administrator|null $administrator
 */
#[Fillable([
    'account_id',
    'first_name',
    'last_name',
    'photo_path',
    'email',
    'email_verified_at',
    'password',
    'password_changed_at',
    'is_active',
    'locale',
    'timezone',
    'time_format',
    'date_format',
    'name_order',
    'number_format',
    'uses_readable_addresses',
    'shows_vault_list',
    'last_login_at',
    'last_login_ip',
    'two_factor_secret',
    'two_factor_confirmed_at',
    'two_factor_recovery_codes',
    'last_saved_at',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, PasskeyUser
{
    /**
     * What a user has before anybody chooses, matching the defaults the columns carry, so a model
     * that has not been read back from the database answers what the database would.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'uses_readable_addresses' => true,
        'shows_vault_list' => true,
    ];

    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use PasskeyAuthenticatable;
    use SoftDeletes;

    /**
     * The size, in CSS pixels, the photo is displayed at. It is written to disk
     * twice, at this size and at twice it, so a dense screen has a sharp
     * version to pick from. 96 covers the 74 pixel avatar on the profile
     * screen, and downsizes cleanly to the 26 pixel one in the sidebar.
     */
    public const int PHOTO_SIZE = 96;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'time_format' => TimeFormatEnum::class,
            'date_format' => DateFormatEnum::class,
            'name_order' => NameOrderEnum::class,
            'number_format' => NumberFormatEnum::class,
            'uses_readable_addresses' => 'boolean',
            'shows_vault_list' => 'boolean',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'last_saved_at' => 'datetime',
            'welcomed_at' => 'datetime',
            'full_access_offer_dismissed_at' => 'datetime',
        ];
    }

    /**
     * Get the account the user belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the magic links issued for the user.
     *
     * @return HasMany<MagicLink, $this>
     */
    public function magicLinks(): HasMany
    {
        return $this->hasMany(MagicLink::class);
    }

    /**
     * Get the actions the user performed.
     *
     * @return HasMany<Log, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get the support requests the user opened.
     *
     * @return HasMany<SupportRequest, $this>
     */
    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }

    /**
     * Get the OAuth access tokens the user granted to assistants. The API keys are the Sanctum
     * tokens behind tokens(); Passport's own trait cannot sit next to Sanctum's, so this relation
     * is declared here instead.
     *
     * @return HasMany<Token, $this>
     */
    public function oauthAccessTokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * Get the emails the application sent to the user.
     *
     * @return HasMany<EmailSent, $this>
     */
    public function emailsSent(): HasMany
    {
        return $this->hasMany(EmailSent::class);
    }

    /**
     * Get the administrator capacity the user holds, if they hold it.
     *
     * @return HasOne<Administrator, $this>
     */
    public function administrator(): HasOne
    {
        return $this->hasOne(Administrator::class);
    }

    /**
     * Get the security keys that answer the two factor challenge for the user,
     * oldest first.
     *
     * @return HasMany<SecurityKey, $this>
     */
    public function securityKeys(): HasMany
    {
        return $this->hasMany(SecurityKey::class)->oldest('id');
    }

    /**
     * Get the outside providers that sign the user in.
     *
     * @return HasMany<ProviderConnection, $this>
     */
    public function providerConnections(): HasMany
    {
        return $this->hasMany(ProviderConnection::class);
    }

    /**
     * Get the name the user is called by, in the order they asked their name to
     * read in. The interface language cannot decide that order: somebody
     * reading Monica in English may still want their family name first.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => ($this->name_order ?? NameOrderEnum::GivenFirst)
                ->format($this->first_name, $this->last_name),
        );
    }

    /**
     * Get whether the user signs in through an SSO provider rather than with a
     * password.
     */
    public function usesSingleSignOn(): bool
    {
        return $this->providerConnections()->exists();
    }

    /**
     * Get whether the user has no password, which is the case for an account
     * created through a provider or a passkey, and for one whose owner has since
     * given their password up. What signs them in instead is asked for by name:
     * `usesSingleSignOn()` and `hasPasskeys()`.
     */
    public function usesPasswordlessSignIn(): bool
    {
        return $this->password === null;
    }

    /**
     * Get whether the user holds at least one passkey, which is what removing
     * their password requires.
     */
    public function hasPasskeys(): bool
    {
        return $this->passkeys()->exists();
    }

    /**
     * Get whether the user is shown the banner offering full access: the
     * account is metered, holds no full access, and the user never hid it.
     * The dismissal is read first, since it costs no query.
     */
    public function isOfferedFullAccess(): bool
    {
        if ($this->full_access_offer_dismissed_at !== null) {
            return false;
        }

        if (! $this->account->isMetered()) {
            return false;
        }

        return ! $this->account->hasFullAccess();
    }

    /**
     * Get whether the user runs the instance. Somebody who cannot sign in
     * cannot administer either: a capacity granted to a user who was then
     * deactivated is not a way back in.
     */
    public function isAdministrator(): bool
    {
        return $this->is_active && $this->administrator()->exists();
    }

    /**
     * Ask an ability about this user and get the answer with its reason.
     *
     * {@see can()} answers the same question with a bare boolean, which is
     * enough to decide whether a control is active. This keeps the response, so
     * the caller can also say why it was refused, in the wording the policy
     * holds and every surface shares. A screen asks this way: it has a reason to
     * show and nothing to interrupt.
     *
     * @param  \UnitEnum|string  $ability
     * @param  mixed  $arguments
     */
    public function inspect($ability, $arguments = []): Response
    {
        return app(Gate::class)->forUser($this)->inspect($ability, $arguments);
    }

    /**
     * Refuse the ability unless this user holds it.
     *
     * {@see inspect()} hands the same answer back to be read. This one refuses
     * instead, and the refusal carries the wording the policy holds, so an
     * action states neither the rule nor the reason a second time.
     *
     * Both ask about this user rather than the signed in session, so a job, a
     * command and a device synchronising over CardDAV all get the same answer.
     *
     * @param  \UnitEnum|string  $ability
     * @param  mixed  $arguments
     *
     * @throws AuthorizationException
     */
    public function authorize($ability, $arguments = []): Response
    {
        return app(Gate::class)->forUser($this)->authorize($ability, $arguments);
    }

    /**
     * Get whether the user finished enrolling an authenticator app, the one
     * second factor that answers with a typed code.
     */
    public function usesAuthenticatorApp(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Get whether the user has to answer a challenge before they are signed in:
     * they hold an authenticator app, a security key, or both. A passkey
     * answers that challenge but never turns it on.
     */
    public function usesTwoFactorAuthentication(): bool
    {
        if ($this->usesAuthenticatorApp()) {
            return true;
        }

        return $this->securityKeys()->exists();
    }

    /**
     * Every pixel size actually written to disk, which is what the route that
     * serves the photo accepts.
     *
     * @return list<int>
     */
    public static function photoPixelSizes(): array
    {
        return [self::PHOTO_SIZE, self::PHOTO_SIZE * 2];
    }

    public function hasPhoto(): bool
    {
        return $this->photo_path !== null;
    }

    /**
     * The path of one version of the photo. photo_path holds the base the two
     * versions are named after, without an extension, so the files beside it
     * are photos/{user}/{uuid}_96.webp and photos/{user}/{uuid}_192.webp.
     */
    public function photoVariantPath(int $pixels): string
    {
        return $this->photo_path.'_'.$pixels.'.webp';
    }

    /**
     * Get the password the framework fingerprints for sessions and the remember cookie.
     *
     * An account made through a passkey or a provider has no password, and the framework
     * refuses to recall a remembered browser whose account password is not a string. An
     * empty string lets it recognise one, and nothing can sign in with it: the hasher
     * refuses to check anything against an empty hash.
     */
    #[Override]
    public function getAuthPassword(): string
    {
        return $this->password ?? '';
    }

    /**
     * Get the preferred locale of the entity.
     *
     * @return string
     */
    #[Override]
    public function preferredLocale()
    {
        return $this->locale;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    #[Override]
    public function sendEmailVerificationNotification()
    {
        new SendEmailVerification(user: $this)->execute();
    }

    /**
     * Send the email carrying the link to choose a new password.
     *
     * @param  string  $token
     * @return void
     */
    #[Override]
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetRequested(token: $token));
    }

    /**
     * The conversation a Telegram notification goes to. The bot token is the
     * instance's and comes from the configuration, so only the destination is
     * answered here.
     */
    public function routeNotificationForTelegram(?LaravelNotification $notification = null): ?string
    {
        return $this->channelBehind($notification)?->destination;
    }

    /**
     * The number a text message goes to. The key and the secret travel on the
     * message, which builds its own client from them.
     */
    public function routeNotificationForVonage(?LaravelNotification $notification = null): ?string
    {
        return $this->channelBehind($notification)?->destination;
    }

    private function channelBehind(?LaravelNotification $notification): ?NotificationChannel
    {
        return $notification instanceof CarriesOnAccountChannel
            ? $notification->notificationChannel()
            : null;
    }
}
