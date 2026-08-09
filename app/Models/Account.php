<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Account
 *
 * The root of everything. Every row of user data hangs off an account, directly
 * or through a vault. An account holds exactly one person, and is what storage
 * is counted against and what a purchase is attached to.
 *
 * @property int $id
 * @property bool $is_self_hosted
 * @property bool $has_lifetime_access
 * @property Carbon|null $purchased_at
 * @property string|null $billing_email
 * @property int $storage_used_in_bytes
 * @property int|null $storage_limit_in_bytes
 * @property Carbon|null $onboarding_completed_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $table = 'accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'is_self_hosted',
        'has_lifetime_access',
        'purchased_at',
        'billing_email',
        'storage_used_in_bytes',
        'storage_limit_in_bytes',
        'onboarding_completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_self_hosted' => 'boolean',
            'has_lifetime_access' => 'boolean',
            'purchased_at' => 'datetime',
            'storage_used_in_bytes' => 'integer',
            'storage_limit_in_bytes' => 'integer',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /**
     * Get the person the account belongs to.
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the vaults the account is divided into.
     *
     * @return HasMany<Vault, $this>
     */
    public function vaults(): HasMany
    {
        return $this->hasMany(Vault::class);
    }

    /**
     * Get the contacts documented in the account, across every vault.
     *
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the sets of choices the fields of the account pick from.
     *
     * @return HasMany<ValueList, $this>
     */
    public function valueLists(): HasMany
    {
        return $this->hasMany(ValueList::class);
    }

    /**
     * Get the kinds of object the account invented for itself, beyond the
     * contacts every account has.
     *
     * @return HasMany<EntityType, $this>
     */
    public function entityTypes(): HasMany
    {
        return $this->hasMany(EntityType::class);
    }

    /**
     * Get the actions performed in the account.
     *
     * @return HasMany<Log, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get the emails sent on behalf of the account.
     *
     * @return HasMany<EmailSent, $this>
     */
    public function emailsSent(): HasMany
    {
        return $this->hasMany(EmailSent::class);
    }

    /**
     * Get whether the account is metered. A self hosted instance never is, and
     * neither is an account with no ceiling on the cloud one.
     */
    public function isMetered(): bool
    {
        return ! $this->is_self_hosted && $this->storage_limit_in_bytes !== null;
    }
}
