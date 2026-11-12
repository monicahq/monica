<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessKindEnum;
use App\Enums\OfferKindEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\StripeCatalogue;
use Carbon\CarbonImmutable;
use Database\Factories\AccountFactory;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Billable;
use Laravel\Pennant\Concerns\HasFeatures;
use Override;

/**
 * Class Account
 *
 * The root of everything. Every row of user data hangs off an account, directly
 * or through a vault. An account holds exactly one person, and is what storage
 * is counted against and what a purchase is attached to.
 *
 * @property int $id
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property CarbonImmutable|null $trial_ends_at
 * @property CarbonImmutable|null $access_granted_at
 * @property string|null $billing_email
 * @property int $storage_used_in_bytes
 * @property int|null $storage_limit_in_bytes
 * @property CarbonImmutable|null $onboarding_completed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'access_granted_at',
    'billing_email',
    'storage_used_in_bytes',
    'storage_limit_in_bytes',
    'onboarding_completed_at',
])]
class Account extends Model
{
    /**
     * What each account answered for {@see usesReadableAddresses()}, for the length of one request.
     *
     * @var array<int, bool>
     */
    private static array $readableAddresses = [];

    use Billable;

    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    use HasFeatures;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'access_granted_at' => 'datetime',
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
     * The name Stripe files the customer under. An account has no name of its own,
     * so it is the name of the person on it.
     */
    public function stripeName(): ?string
    {
        return $this->user?->name;
    }

    /**
     * The address Stripe writes to, which is the address the person on the account
     * signs in with.
     */
    public function stripeEmail(): ?string
    {
        return $this->user?->email;
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
     * Whether the product builds addresses that name this account's vaults and contacts.
     *
     * Read from the account rather than from whoever is signed in, for the same reason the language
     * is: a queued notification has to build the address a request would.
     *
     * Answered once per request per account, because {@see Vault::getRouteKey()} asks
     * for every link on a page and a list of fifty contacts would otherwise ask fifty times. The
     * memo lives for one request, so a preference saved in one is read afresh in the next.
     */
    public function usesReadableAddresses(): bool
    {
        return self::buildsReadableAddressesFor($this->id);
    }

    /**
     * The same answer for an account nobody has loaded.
     *
     * A vault and a contact both carry `account_id` and neither needs the row behind it to decide
     * which address to build, so this is asked by id. Loading the relation instead would put one
     * query behind every link on a page listing fifty contacts.
     *
     * On a request the answer is already in memory, on the user the request signed in: reading it
     * there keeps a page costing exactly the queries it cost before. A queued job has nobody signed
     * in and asks the database, once per account for as long as the process lives.
     */
    public static function buildsReadableAddressesFor(int $accountId): bool
    {
        $signedIn = Auth::user();

        if ($signedIn instanceof User && $signedIn->account_id === $accountId) {
            return $signedIn->uses_readable_addresses;
        }

        return self::$readableAddresses[$accountId] ??= (bool) (
            User::withTrashed()->where('account_id', $accountId)->value('uses_readable_addresses') ?? true
        );
    }

    /**
     * Forget what was read, which a test changing the preference mid request needs.
     */
    public static function forgetAddressPreferences(): void
    {
        self::$readableAddresses = [];
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
     * Get the support requests opened from the account.
     *
     * @return HasMany<SupportRequest, $this>
     */
    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
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
     * Get the places the things of the account are kept in, at every level.
     * They belong to the account rather than to a vault, since one house
     * holds the things of every vault.
     *
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the kinds of things the account owns, each with the fields a thing
     * of that kind carries. Like locations, they belong to the account rather
     * than to a vault.
     *
     * @return HasMany<PossessionType, $this>
     */
    public function possessionTypes(): HasMany
    {
        return $this->hasMany(PossessionType::class);
    }

    /**
     * Get the words the account puts on its possessions to sort them across
     * types and locations. Like possession types, they belong to the account
     * rather than to a vault.
     *
     * @return HasMany<PossessionTag, $this>
     */
    public function possessionTags(): HasMany
    {
        return $this->hasMany(PossessionTag::class);
    }

    /**
     * Get the colour a tag created from a possession's page takes: the vault
     * colour after the colour of the account's latest tag, wrapping after the
     * last one, so tags created in a row do not all look alike. An account
     * without a tag starts at the first colour.
     */
    public function nextPossessionTagColour(): VaultColourEnum
    {
        $latest = $this->possessionTags()->latest('id')->value('colour');
        $colours = VaultColourEnum::cases();
        $position = array_search(VaultColourEnum::tryFrom((string) $latest), $colours, true);

        if ($position === false) {
            return $colours[0];
        }

        return $colours[($position + 1) % count($colours)];
    }

    /**
     * Get the possession conditions of the account, in the order it keeps them,
     * from best to worst. Like possession tags, they belong to the account
     * rather than to a vault.
     *
     * @return HasMany<PossessionCondition, $this>
     */
    public function possessionConditions(): HasMany
    {
        return $this->hasMany(PossessionCondition::class)->orderBy('position')->orderBy('id');
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
     * Get the groups the life event vocabulary is filed under.
     *
     * @return HasMany<LifeEventCategory, $this>
     */
    public function lifeEventCategories(): HasMany
    {
        return $this->hasMany(LifeEventCategory::class)->orderBy('position');
    }

    /**
     * Get every nature a life event may be recorded as, across the groups they are filed under.
     *
     * @return HasMany<LifeEventType, $this>
     */
    public function lifeEventTypes(): HasMany
    {
        return $this->hasMany(LifeEventType::class);
    }

    /**
     * Get the display groups the relationship vocabulary is filed under.
     *
     * @return HasMany<RelationshipCategory, $this>
     */
    public function relationshipCategories(): HasMany
    {
        return $this->hasMany(RelationshipCategory::class)->orderBy('position');
    }

    /**
     * Get the kinds of link the account can put between two people.
     *
     * @return HasMany<RelationshipType, $this>
     */
    public function relationshipTypes(): HasMany
    {
        return $this->hasMany(RelationshipType::class);
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
     * Get every channel the account may be reached on, switched on or not.
     *
     * There is one row per channel, made with the account, so a screen listing
     * them never has to invent the ones that are missing. What a channel needs
     * before it can carry anything is a fact about the channel and is asked of
     * `NotificationChannelEnum` rather than read from a column.
     *
     * @return HasMany<NotificationChannel, $this>
     */
    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    /**
     * Get every call an assistant made to a tool of the MCP server.
     *
     * @return HasMany<McpCall, $this>
     */
    public function mcpCalls(): HasMany
    {
        return $this->hasMany(McpCall::class);
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
     * Get the payments Stripe confirmed for the account, newest first. Stripe
     * keeps the invoices; these rows are what a screen reads so that none of
     * them waits on the provider.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('billed_at');
    }

    /**
     * Get whether the account is metered. A self hosted instance meters nobody,
     * which is a setting of the instance and never a fact stamped on a row, and
     * the hosted one does not meter an account with no ceiling.
     */
    public function isMetered(): bool
    {
        if (config('monica.self_hosted')) {
            return false;
        }

        return $this->storage_limit_in_bytes !== null;
    }

    /**
     * Get the orders the account placed at Stripe, newest first. An abandoned
     * checkout leaves one here too, unpaid for good and holding nothing.
     *
     * @return HasMany<StripeOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(StripeOrder::class)->latest();
    }

    /**
     * Get the order that holds full access, null when none does. Read off the
     * loaded relation, so a screen asking several times asks the database once.
     */
    public function settledOrder(): ?StripeOrder
    {
        return $this->orders->firstWhere('stripe_status', StripeOrder::PAID);
    }

    /**
     * Get whether the account holds full access, however it came to hold it:
     * bought once, granted by an administrator, or through a subscription Stripe
     * still considers valid. The caps read this and nothing else.
     */
    public function hasFullAccess(): bool
    {
        if ($this->holdsAccessForGood()) {
            return true;
        }

        return $this->subscribed();
    }

    /**
     * Get how full access is held, null on the free offer. Each of the three is
     * written down, so none of them is guessed at.
     */
    public function accessKind(): ?AccessKindEnum
    {
        if ($this->settledOrder() !== null) {
            return AccessKindEnum::Lifetime;
        }

        if ($this->access_granted_at !== null) {
            return AccessKindEnum::Granted;
        }

        if ($this->subscribed()) {
            return AccessKindEnum::Subscription;
        }

        return null;
    }

    /**
     * Get whether the account holds access that never renews, which is what an
     * order and a grant have in common and what closes the offers.
     */
    public function holdsAccessForGood(): bool
    {
        return $this->settledOrder() !== null || $this->access_granted_at !== null;
    }

    /**
     * Get the offers the account may still take up, out of Stripe's catalogue:
     * none on a self hosted instance, none once lifetime access is held, and
     * while a subscription runs only what sits above it, meaning lifetime access
     * and the subscriptions longer than the current one, or than the one it
     * already moves to at its next renewal.
     *
     * @return array<int, array{price_id: string, kind: OfferKindEnum, amount: int, currency: string, prices: array<string, int>}>
     */
    public function availableOffers(): array
    {
        if (config('monica.self_hosted')) {
            return [];
        }

        if ($this->holdsAccessForGood()) {
            return [];
        }

        $subscription = $this->subscription();
        $subscribed = $subscription?->valid() === true;
        $current = $subscribed && ! $subscription->onGracePeriod()
            ? $this->scheduledOfferKind() ?? StripeCatalogue::kindOfSubscription($subscription)
            : null;

        return array_values(array_filter(StripeCatalogue::offers(), function (array $offer) use ($subscribed, $current): bool {
            if (! $subscribed) {
                return true;
            }

            if (! $offer['kind']->renews()) {
                return true;
            }

            return $current !== null && $offer['kind']->isAbove($current);
        }));
    }

    /**
     * Get whether the offers could be read from Stripe. True whenever there is
     * nothing to sell this account, so a self hosted instance and a lifetime
     * holder never ask Stripe at all.
     */
    public function offersAvailable(): bool
    {
        if (config('monica.self_hosted')) {
            return true;
        }

        if ($this->holdsAccessForGood()) {
            return true;
        }

        return StripeCatalogue::isAvailable();
    }

    /**
     * Get which offer the running subscription is on, read off its price, null
     * when nothing is running or the catalogue does not know the price.
     */
    public function subscriptionOfferKind(): ?OfferKindEnum
    {
        $subscription = $this->subscription();

        if ($subscription === null) {
            return null;
        }

        if (! $subscription->valid()) {
            return null;
        }

        return StripeCatalogue::kindOfSubscription($subscription);
    }

    /**
     * Get which offer the running subscription moves to at its next renewal, null
     * when no move is waiting or the subscription is ending.
     */
    public function scheduledOfferKind(): ?OfferKindEnum
    {
        $subscription = $this->subscription();

        if ($subscription === null) {
            return null;
        }

        if (! $subscription->valid()) {
            return null;
        }

        if ($subscription->onGracePeriod()) {
            return null;
        }

        if ($subscription->scheduled_stripe_price === null) {
            return null;
        }

        return StripeCatalogue::kindOf($subscription->scheduled_stripe_price);
    }

    /**
     * Get when the subscription next bills, null when nothing renews. Stripe
     * holds the period rather than the local row, so this is one round trip, and
     * a Stripe that does not answer costs a date rather than a screen.
     */
    public function nextBillingDate(): ?CarbonImmutable
    {
        $subscription = $this->subscription();

        if ($subscription === null) {
            return null;
        }

        if (! $subscription->valid()) {
            return null;
        }

        if ($subscription->onGracePeriod()) {
            return null;
        }

        try {
            $renewsAt = $subscription->currentPeriodEnd();
        } catch (Exception) {
            return null;
        }

        return $renewsAt === null ? null : CarbonImmutable::instance($renewsAt);
    }

    /**
     * Get whether the account is on the free offer, which is the one state the
     * caps apply to: a hosted instance, and no full access.
     */
    public function isOnFreeOffer(): bool
    {
        if (config('monica.self_hosted')) {
            return false;
        }

        return ! $this->hasFullAccess();
    }

    /**
     * Get the storage ceiling that applies right now, null when there is none.
     *
     * The stamped column is the free ceiling and is never rewritten. Full access
     * lifts it to the paid figure, so a refund or a lapse has nothing to put back,
     * and a ceiling an administrator raised above that figure is kept.
     */
    public function storageLimitInBytes(): ?int
    {
        if (! $this->isMetered()) {
            return null;
        }

        if (! $this->hasFullAccess()) {
            return $this->storage_limit_in_bytes;
        }

        return max((int) $this->storage_limit_in_bytes, (int) config('monica.paid_storage_limit_in_bytes'));
    }

    /**
     * Get how many vaults the account may hold, null when there is no ceiling.
     */
    public function vaultLimit(): ?int
    {
        return $this->isOnFreeOffer() ? (int) config('monica.free_vault_limit') : null;
    }

    /**
     * Get how many contacts the account may hold, null when there is no ceiling.
     */
    public function contactLimit(): ?int
    {
        return $this->isOnFreeOffer() ? (int) config('monica.free_contact_limit') : null;
    }

    /**
     * Count the contacts the cap is measured against: every vault of the account,
     * archived ones included, leaving out each vault's Me contact and anybody
     * still waiting to be filled in.
     */
    public function contactCount(): int
    {
        return $this->contacts()->where('is_me', false)->where('is_partial', false)->count();
    }

    /**
     * Get the folder holding the working files of the account's exports and
     * imports, one folder per export or import beneath it, so removing it removes
     * every file a transfer left behind.
     */
    public function transfersDirectory(): string
    {
        return "accounts/{$this->id}/transfers";
    }
}
