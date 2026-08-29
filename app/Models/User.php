<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DateFormatEnum;
use App\Enums\NameOrderEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
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
 * @property string|null $sso_provider
 * @property bool $is_active
 * @property string|null $locale
 * @property string $timezone
 * @property TimeFormatEnum $time_format
 * @property DateFormatEnum|null $date_format
 * @property NameOrderEnum $name_order
 * @property NumberFormatEnum|null $number_format
 * @property CarbonImmutable|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $two_factor_secret
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $last_saved_at
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
    'sso_provider',
    'is_active',
    'locale',
    'timezone',
    'time_format',
    'date_format',
    'name_order',
    'number_format',
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
class User extends Authenticatable implements HasLocalePreference
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
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
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'last_saved_at' => 'datetime',
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
     * Get the passkeys the user registered. The order is the caller's business:
     * the security screen wants the most recently used first, and nothing else
     * reads them as a list.
     *
     * @return HasMany<WebauthnKey, $this>
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(WebauthnKey::class);
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
        return $this->sso_provider !== null;
    }

    /**
     * Get whether the user has given up their password and signs in with a
     * passkey instead.
     *
     * A null password used to mean one thing, that the user signs in through an
     * identity provider. It now means either that or this, so the two are asked
     * for by name rather than by testing the column at each call site.
     */
    public function usesPasswordlessSignIn(): bool
    {
        return $this->password === null && $this->sso_provider === null;
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
     * Get whether the user runs the instance. Somebody who cannot sign in
     * cannot administer either: a capacity granted to a user who was then
     * deactivated is not a way back in.
     */
    public function isAdministrator(): bool
    {
        return $this->is_active && $this->administrator()->exists();
    }

    /**
     * Get whether the user confirmed their email address.
     */
    public function hasConfirmedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Get whether the user finished enrolling in two factor authentication, and
     * therefore has to answer a challenge before they are signed in.
     */
    public function usesTwoFactorAuthentication(): bool
    {
        return $this->two_factor_confirmed_at !== null;
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
     * Get the preferred locale of the entity.
     *
     * @return string
     */
    #[Override]
    public function preferredLocale()
    {
        return $this->locale;
    }
}
