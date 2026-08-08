<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NameOrderEnum;
use App\Enums\TimeFormatEnum;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property Carbon|null $password_changed_at
 * @property string|null $sso_provider
 * @property bool $is_active
 * @property string|null $locale
 * @property string $timezone
 * @property TimeFormatEnum $time_format
 * @property string|null $date_format
 * @property NameOrderEnum $name_order
 * @property string|null $number_format
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon|null $last_saved_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $name
 */
class User extends Authenticatable
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

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'time_format' => TimeFormatEnum::class,
            'name_order' => NameOrderEnum::class,
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
}
