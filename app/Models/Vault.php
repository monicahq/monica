<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountImportStatusEnum;
use App\Enums\ContactGroupingEnum;
use App\Enums\ValueListEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\ReservedAddressWords;
use App\Helpers\SlugBuilder;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\VaultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Override;

/**
 * Class Vault
 *
 * A watertight compartment of data: "Famille", "Pro", "Amis". Contacts,
 * entities and life events live in one, and nothing reaches across. Separating
 * contexts is the whole point, so that a work search never returns a family
 * contact and a private link scoped to one part of a life exposes no other.
 *
 * A vault is addressed two ways, and answers to both. The slug is built from
 * the name its owner gave it and is what the product writes in URLs and in the
 * API, so an address can be read before it is followed. It is not permanent:
 * renaming the vault rebuilds it, the one it had stops answering, and the one
 * it had is reserved for good so no other vault of the account is ever given
 * it. The uuid never changes and never stops answering, which is what keeps
 * every link saved before any of this working, and it is what the product
 * writes when the account asks for addresses that name nothing.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property int $account_id
 * @property string $name
 * @property string|null $description
 * @property string $colour
 * @property int $position
 * @property bool $is_default
 * @property int $contact_count
 * @property bool $shows_people Whether the People section appears in the sidebar and in the search of the vault.
 * @property bool $shows_things Whether the Things section appears in the sidebar of the vault.
 * @property bool $shows_knowledge Whether the Knowledge section appears in the sidebar of the vault.
 * @property CarbonImmutable|null $last_visited_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read int|null $upcoming_reminder_count Present only when the query asked for it with `withCardValues()`.
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'slug',
    'name',
    'description',
    'colour',
    'position',
    'is_default',
    'contact_count',
    'shows_people',
    'shows_things',
    'shows_knowledge',
    'last_visited_at',
])]
class Vault extends Model
{
    /** @use HasFactory<VaultFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;
    use SoftDeletes;

    /**
     * The colour a vault is given when whoever created it did not pick one.
     * Every colour on offer is a case of {@see VaultColourEnum}.
     */
    public const string DEFAULT_COLOUR = VaultColourEnum::Blue->value;

    /**
     * How much room the readable address has, and what it falls back on.
     */
    public const int SLUG_LENGTH = 128;

    public const string SLUG_WHEN_EMPTY = 'vault';

    /**
     * How many contacts a vault card draws as faces.
     */
    public const int FACES_SHOWN = 4;

    /**
     * Every section is shown until somebody hides it, so a vault built in memory says so before it is
     * saved, as the database default does after.
     *
     * @var array<string, bool>
     */
    protected $attributes = [
        'shows_people' => true,
        'shows_things' => true,
        'shows_knowledge' => true,
    ];

    /**
     * Give the vault the address it is read at, here rather than in the actions, because every path
     * that creates one meets here and an address is not optional.
     *
     * The reservation is written once the row has an id, and is never removed. It is what makes the
     * slug spent for good: a vault renamed away from `famille` leaves that address answering
     * nobody, rather than answering whichever vault is called Famille next.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (Vault $vault): void {
            if (($vault->slug ?? '') !== '' && ! $vault->isDirty('name')) {
                return;
            }

            $vault->slug = SlugBuilder::readable(
                derivedFrom: (string) $vault->name,
                maxLength: self::SLUG_LENGTH,
                whenEmpty: self::SLUG_WHEN_EMPTY,
                isTaken: fn (string $slug): bool => $vault->slugIsSpent($slug),
            );
        });

        static::saved(function (Vault $vault): void {
            VaultSlugReservation::query()->firstOrCreate([
                'account_id' => $vault->account_id,
                'slug' => $vault->slug,
            ], [
                'vault_id' => $vault->id,
            ]);
        });
    }

    /**
     * Whether another vault of this account has ever answered to the slug, or a route already fixes
     * it.
     *
     * Only the reservations are read. Every slug a vault is given is reserved in the same save, so
     * the reservations are a superset of what the vaults themselves carry. A vault does not collide
     * with its own past: renaming one back to what it was called gives it the same address again,
     * which points at the vault it always pointed at.
     */
    private function slugIsSpent(string $slug): bool
    {
        if (in_array($slug, ReservedAddressWords::forVault(), true)) {
            return true;
        }

        return VaultSlugReservation::query()
            ->where('account_id', $this->account_id)
            ->where('slug', $slug)
            ->when($this->id !== null, fn (Builder $query) => $query->where('vault_id', '!=', $this->id))
            ->exists();
    }

    /**
     * The two spellings of this record's address.
     *
     * The readable one is tried first, being the one the product builds. The uuid is tried after it
     * and never stops working: it is what every link saved before the readable address existed is
     * written with, and what an address still names once a rename has released the readable one.
     *
     * @return list<string>
     */
    protected function routeKeyColumns(): array
    {
        return ['slug', 'uuid'];
    }

    /**
     * The address the product builds for this record.
     *
     * The readable name unless the account asked for the identifier instead. It governs only what
     * is built: both spellings keep answering either way, which is what stops the preference
     * breaking a link somebody saved.
     */
    #[Override]
    public function getRouteKey(): mixed
    {
        return Account::buildsReadableAddressesFor($this->account_id) ? $this->slug : $this->uuid;
    }

    /**
     * Scope the query to the record an address names, in either spelling of it.
     *
     * The API resolves its own records rather than leaning on route model binding, so the rule that
     * both spellings answer has to hold here too. Everything else about the query is left alone:
     * this widens which column is read and never which records are in reach. An address that is not
     * a uuid is never compared against the uuid column, which a database may refuse outright.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAddressedBy(Builder $query, string $address): void
    {
        $query->where(function (Builder $query) use ($address): void {
            $query->where('slug', $address);

            if (Str::isUuid($address)) {
                $query->orWhere('uuid', $address);
            }
        });
    }

    /**
     * Scope the query to load what a vault card shows besides the vault itself: its faces with
     * their avatars, and how many reminders still wait on an answer before the month is out,
     * overdue ones included. The month is the one the given person is living in.
     *
     * The web card and the API both read the card through here, so the two cannot disagree.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeWithCardValues(Builder $query, User $user): void
    {
        $endOfMonth = CarbonImmutable::now($user->timezone)->endOfMonth();

        $query
            ->with('faces.avatar')
            ->addSelect([
                'upcoming_reminder_count' => ReminderOccurrence::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('reminder_occurrences.vault_id', 'vaults.id')
                    ->awaitingAnswerUntil($endOfMonth),
            ]);
    }

    /**
     * Scope the query to the order of the vaults page: the vault opened most recently first, the
     * ones never opened last, then by position.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOrderedByRecentVisit(Builder $query): void
    {
        $query
            ->orderByRaw('last_visited_at is null')
            ->orderByDesc('last_visited_at')
            ->orderBy('position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
            'contact_count' => 'integer',
            'shows_people' => 'boolean',
            'shows_things' => 'boolean',
            'shows_knowledge' => 'boolean',
            'last_visited_at' => 'datetime',
        ];
    }

    /**
     * Get the account the vault belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contacts the vault holds. Nothing outside the vault reaches
     * them, which is the whole point of the compartment.
     *
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the contacts a vault card shows as faces: the four listed most recently, the newest first.
     * Eager loaded for many vaults, the limit applies to each vault rather than to all of them.
     *
     * @return HasMany<Contact, $this>
     */
    public function faces(): HasMany
    {
        return $this->contacts()
            ->listed()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::FACES_SHOWN);
    }

    /**
     * Get the records of the invented kinds the vault holds: the animals, the
     * companies, the bottles. They are compartmented exactly as contacts are.
     *
     * @return HasMany<Entity, $this>
     */
    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }

    /**
     * Get the things owned that are recorded in the vault.
     *
     * @return HasMany<Possession, $this>
     */
    public function possessions(): HasMany
    {
        return $this->hasMany(Possession::class);
    }

    /**
     * Get the folders of the vault's Knowledge, in their order.
     *
     * @return HasMany<KnowledgeFolder, $this>
     */
    public function knowledgeFolders(): HasMany
    {
        return $this->hasMany(KnowledgeFolder::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Get the structures a new record starts on here, one per kind of record.
     * They differ from vault to vault, since a work contact and a family
     * contact do not start from the same page.
     *
     * @return HasMany<VaultDefaultTemplate, $this>
     */
    public function defaultTemplates(): HasMany
    {
        return $this->hasMany(VaultDefaultTemplate::class);
    }

    /**
     * Get the columns the list of people shows here, after the name, in their
     * order. None until somebody chooses them, and the defaults apply until then.
     *
     * @return HasMany<ContactListColumn, $this>
     */
    public function contactListColumns(): HasMany
    {
        return $this->hasMany(ContactListColumn::class)->orderBy('position');
    }

    /**
     * Get the private links opened onto the vault itself, newest first. The
     * links pointing at one of its contacts are not these: they hang off the
     * contact, and carry the vault only to say where that contact lives.
     *
     * @return MorphMany<ShareLink, $this>
     */
    public function shareLinks(): MorphMany
    {
        return $this->morphMany(ShareLink::class, 'shareable')->latest();
    }

    /**
     * Get the slugs the vault has carried. A rename releases the slug the vault
     * answered to, and this is what stops it being given to another vault of
     * the account, so an address saved before the rename fails rather than
     * opening something else.
     *
     * @return HasMany<VaultSlugReservation, $this>
     */
    public function slugReservations(): HasMany
    {
        return $this->hasMany(VaultSlugReservation::class);
    }

    /**
     * Get the categories somebody in the vault holds, each with how many people hold it.
     *
     * A category is a choice of the account's "Contact categories" list. Only the people of the
     * grouping count, a choice no longer offered is still listed while somebody holds it, and a
     * person holding a category in two fields counts once. The list is in the order of the list.
     * A caller that does not reach sensitive fields neither sees nor counts what one of them holds.
     *
     * @return list<array{item: ValueListItem, contacts: int}>
     */
    public function categoriesHeldByContacts(ContactGroupingEnum $grouping, bool $reachesSensitiveFields): array
    {
        $list = ValueList::query()
            ->where('account_id', $this->account_id)
            ->where('key', ValueListEnum::ContactCategories->value)
            ->first();

        if ($list === null) {
            return [];
        }

        $counts = FieldValue::query()
            ->where('vault_id', $this->id)
            ->where('valuable_type', new Contact()->getMorphClass())
            ->whereIn('valuable_id', $this->contacts()->inGrouping($grouping)->select('id'))
            ->whereIn('field_id', Field::query()
                ->where('value_list_id', $list->id)
                ->when(! $reachesSensitiveFields, fn (Builder $fields): Builder => $fields->where('is_sensitive', false))
                ->select('id'))
            ->where('is_detached', false)
            ->whereNotNull('value_list_item_ids')
            ->get(['valuable_id', 'value_list_item_ids'])
            ->groupBy('valuable_id')
            ->flatMap(fn (EloquentCollection $values): array => $values
                ->flatMap(fn (FieldValue $value): array => array_map(intval(...), $value->value_list_item_ids ?? []))
                ->unique()
                ->all())
            ->countBy();

        return ValueListItem::query()
            ->where('value_list_id', $list->id)
            ->whereIn('id', $counts->keys())
            ->orderBy('position')
            ->get()
            ->map(fn (ValueListItem $item): array => ['item' => $item, 'contacts' => (int) $counts->get($item->id)])
            ->values()
            ->all();
    }

    /**
     * Whether an import is writing into the vault or rolling back what it wrote. The search for
     * duplicates waits for it, since the vault is only partly written until the import ends. An
     * account runs one import at a time, so this reads one row at most.
     */
    public function isBeingImportedInto(): bool
    {
        return AccountImport::query()
            ->where('account_id', $this->account_id)
            ->whereIn('status', [AccountImportStatusEnum::Writing, AccountImportStatusEnum::RollingBack])
            ->get()
            ->contains(fn (AccountImport $import): bool => in_array($this->id, $import->vaultIds(), true));
    }
}
