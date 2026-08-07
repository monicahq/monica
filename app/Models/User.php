<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimeFormatEnum;
use Carbon\Carbon;
use Database\Factories\UserFactory;
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
 * @property int $id
 * @property int $company_id
 * @property int|null $employee_id
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password_hash
 * @property Carbon|null $password_changed_at
 * @property string|null $sso_provider
 * @property bool $is_active
 * @property string|null $locale
 * @property TimeFormatEnum $time_format
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'email',
        'email_verified_at',
        'password_hash',
        'password_changed_at',
        'sso_provider',
        'is_active',
        'locale',
        'time_format',
        'last_login_at',
        'last_login_ip',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
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
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password_hash' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * Get the company the user belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee the account gives access to, when the account belongs to
     * somebody who works for the company.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
     * Get the password of the user. The column is named password_hash, so the
     * guard needs to be told where to look.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
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
}
