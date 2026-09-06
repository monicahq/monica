<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

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
 * @property CarbonImmutable|null $purchased_at
 * @property string|null $billing_email
 * @property int $storage_used_in_bytes
 * @property int|null $storage_limit_in_bytes
 * @property CarbonImmutable|null $onboarding_completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'is_self_hosted',
    'has_lifetime_access',
    'purchased_at',
    'billing_email',
    'storage_used_in_bytes',
    'storage_limit_in_bytes',
    'onboarding_completed_at',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
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
     * The language every list in this account is ordered and searched in. One
     * person to an account means one language, which is what lets a key derived
     * from it live on the row.
     *
     * Deliberately not the language of the request: a queued job must write the
     * key a request would, or the row it touches is filed under another alphabet.
     */
    public function locale(): string
    {
        return $this->userIncludingDeleted()->value('locale')
            ?? config('app.locale');
    }

    /**
     * Get the person on the account whether or not they were deleted.
     *
     * The plain relation hides a soft deleted user, which is right everywhere a
     * user is acting and wrong where an account is being looked at: an account
     * whose owner was deleted is exactly the kind somebody writes in about.
     *
     * @return HasOne<User, $this>
     */
    public function userIncludingDeleted(): HasOne
    {
        return $this->hasOne(User::class)->withTrashed();
    }

    /**
     * The name of the person on the account, deleted or not, and a stand in when
     * there is nobody at all. It is what the record of administrator actions
     * reads, which is why it never comes back empty: an entry saying something
     * was done to nobody is an entry nobody can act on.
     */
    public function ownerName(): string
    {
        $owner = User::query()->withTrashed()->where('account_id', $this->id)->first();

        return $owner->name ?? __('an account with nobody on it');
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
     * Get the templates the account defined: what a record of a given kind
     * shows. The template is the stable identity, and what it holds lives in
     * its versions.
     *
     * @return HasMany<Template, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get the reusable sections the account defined. A section is not owned by a
     * template: the same one can be assembled into several, which is why it hangs
     * off the account rather than off whatever placed it.
     *
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
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
     * Get the private links opened out of the account, revoked ones included.
     *
     * @return HasMany<ShareLink, $this>
     */
    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class);
    }

    /**
     * Get how the account wants to be told things.
     *
     * Exactly one row, made with the account, so that delivery never has to
     * handle a missing one. Section 10.4 puts the choice at the account level
     * because every send reads all of it at once.
     *
     * @return HasOne<NotificationPreference, $this>
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get everything the account has been told, on every channel.
     *
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
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
