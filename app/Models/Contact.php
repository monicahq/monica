<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\CollationKey;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactNameKindEnum;
use App\Enums\ContactSortEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\NameOrderEnum;
use App\Enums\RelationshipBehaviorEnum;
use App\Enums\RelationshipStatusEnum;
use App\Enums\SortDirectionEnum;
use App\Enums\ValueListEnum;
use App\Helpers\LocaleCollation;
use App\Helpers\NameFiling;
use App\Helpers\ReservedAddressWords;
use App\Helpers\SlugBuilder;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use App\Models\Concerns\SearchesRecords;
use Carbon\CarbonImmutable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use LogicException;
use Override;

/**
 * Class Contact
 *
 * A person being documented, and the object the whole product orbits.
 *
 * The concept is frozen and its content is not. The structured name is the one
 * thing the product imposes, because it uses it constantly to sort, to search,
 * to render headers and to build initials, and none of that can depend on a
 * field the user might have deleted. Every part of it is optional, including
 * all of them at once: somebody whose existence is known before their name is
 * displays through the translated fallback, which is never stored in a name
 * field. Everything else on the page comes from the structure version it points
 * at.
 *
 * A contact is addressed two ways, and answers to both. The slug is built from
 * the whole name the page shows, in the order that name reads in, keeping the
 * alphabet it is written in so a name in Japanese is addressed in Japanese. It
 * is not permanent: correcting a name rebuilds it, the one it had stops
 * answering, and the one it had is reserved for good so nobody else in the
 * vault is ever given it. The uuid never changes and never stops answering.
 *
 * Two special states live here. One contact per vault is flagged `is_me` and
 * represents the user, so that "Alice is my sister" is a relationship between
 * two rows rather than an invisible parallel logic. A partial contact is
 * somebody written down while a relationship was being entered, kept out of the
 * main lists until they are filled in. Partial and nameless are independent:
 * either a partial or a regular contact may have no name.
 *
 * Two dates are kept from the life events a contact took part in, and never
 * typed in. `last_life_event_at` is the latest of any type. `last_interaction_at`
 * is what the page calls Last in touch: the latest one already past whose type
 * updates it, so news about the contact does not count as having seen them.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property int $account_id
 * @property int $vault_id
 * @property int $structure_version_id
 * @property string|null $prefix
 * @property string|null $first_name
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property int|null $display_name_representation_id
 * @property string|null $formatted_name
 * @property string|null $maiden_name
 * @property string|null $nickname
 * @property NameOrderEnum|null $name_order
 * @property string $sort_name
 * @property string $sort_key
 * @property string $search_key
 * @property string $match_key
 * @property bool $is_me
 * @property bool $is_partial
 * @property bool $is_archived
 * @property bool $is_favourite
 * @property int|null $avatar_file_id
 * @property string $avatar_colour
 * @property CarbonImmutable|null $last_life_event_at
 * @property CarbonImmutable|null $last_interaction_at
 * @property int $life_event_count
 * @property CarbonImmutable|null $last_consulted_at
 * @property string|null $dav_uri
 * @property string|null $vcard
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property bool $deleted_with_vault
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read StructureVersion $structureVersion
 * @property-read File|null $avatar
 * @property-read ContactNameRepresentation|null $displayNameRepresentation
 * @property-read Collection<int, ContactNameRepresentation> $nameRepresentations
 * @property-read Collection<int, GroupMember> $groupMembers
 * @property-read Collection<int, PossessionLoan> $possessionLoans
 * @property-read string $name
 * @property-read string $initials
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'vault_id',
    'slug',
    'structure_version_id',
    'maiden_name',
    'nickname',
    'is_me',
    'is_partial',
    'is_archived',
    'is_favourite',
    'avatar_file_id',
    'avatar_colour',
    'last_life_event_at',
    'last_interaction_at',
    'life_event_count',
    'last_consulted_at',
    'dav_uri',
    'vcard',
])]
#[Hidden([
    'search_name',
    'search_body',
    'search_sensitive_body',
    'search_linked_names',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SearchesRecords;
    use SoftDeletes;

    private bool $isCopyingTheDisplayName = false;

    /**
     * The side of the square an uploaded avatar is cropped to. The biggest
     * place it is drawn is the header of the record at 104, so this stays sharp
     * on a dense screen while a list of forty people costs a couple of
     * megabytes rather than a couple of hundred.
     */
    public const int AVATAR_PIXELS = 512;

    /**
     * The side of the small copy written beside every uploaded avatar. Lists
     * draw an avatar at 48 pixels at most, and this is twice that, so a dense
     * screen stays sharp without fetching the 512 pixel original forty times.
     */
    public const int AVATAR_THUMBNAIL_PIXELS = 96;

    /**
     * How much room the readable address has, and what it falls back on when a name leaves
     * nothing an address can carry.
     */
    public const int SLUG_LENGTH = 128;

    public const string SLUG_WHEN_EMPTY = 'contact';

    /**
     * The columns the readable address is built from, and so the ones whose change rebuilds it.
     */
    public const array NAME_PARTS = [
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'formatted_name',
        'maiden_name',
        'nickname',
        'name_order',
    ];

    /**
     * The columns holding a copy of the display representation. Only {@see copyDisplayName()}
     * writes them once the contact exists.
     */
    public const array DISPLAY_NAME_COPY = [
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'name_order',
        'formatted_name',
    ];

    /**
     * The backgrounds a generated initials avatar is drawn on. They are picked
     * from when a contact is created and kept afterwards, so somebody who has
     * learnt to spot a face in a list does not have it change colour.
     *
     * @var list<string>
     */
    public const array AVATAR_COLOURS = [
        '#0ea5e9',
        '#6366f1',
        '#a855f7',
        '#ec4899',
        '#f97316',
        '#eab308',
        '#22c55e',
        '#14b8a6',
    ];

    /**
     * Write the string the lists sort on and the keys derived from the name, here
     * rather than in the actions: sorting thousands of contacts cannot
     * concatenate at read time, and every path that writes a name meets here.
     *
     * Built for the account's language, not the request's, so a queued job
     * writes what a request would.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (Contact $contact): void {
            if ($contact->isDirty('display_name_representation_id')) {
                $contact->copyDisplayName($contact->displayNameRepresentation()->first());
            }

            if ($contact->exists && ! $contact->isCopyingTheDisplayName && $contact->isDirty(self::DISPLAY_NAME_COPY)) {
                throw new LogicException('A contact is renamed through its name representations, never by writing its name columns.');
            }

            $contact->isCopyingTheDisplayName = false;

            $locale = $contact->account->locale();
            $contact->sort_name = $contact->sortNameFor($locale);
            $contact->sort_key = LocaleCollation::sortKey($contact->sort_name, $locale);
            $contact->search_key = $contact->searchKeyFor($locale);
            $contact->match_key = LocaleCollation::matchKey($contact->buildMatchToken(), $locale);

            if (($contact->slug ?? '') === '' || $contact->isDirty(self::NAME_PARTS)) {
                $contact->slug = SlugBuilder::readable(
                    derivedFrom: $contact->nameForAddress(),
                    maxLength: self::SLUG_LENGTH,
                    whenEmpty: self::SLUG_WHEN_EMPTY,
                    isTaken: fn (string $slug): bool => $contact->slugIsSpent($slug),
                );
            }
        });

        static::saved(function (Contact $contact): void {
            ContactSlugReservation::query()->firstOrCreate([
                'vault_id' => $contact->vault_id,
                'slug' => $contact->slug,
            ], [
                'contact_id' => $contact->id,
            ]);

            if ($contact->wasChanged('search_key')) {
                $contact->resendTheRecordsNamingIt();
            }
        });
    }

    /**
     * The name the readable address is built from: everything the page puts on the line, in the
     * order this particular name reads in.
     *
     * The maiden name is added only when {@see nameInOrder()} has not already fallen back to it,
     * which it does for a contact who has nothing else.
     */
    private function nameForAddress(): string
    {
        $name = $this->nameInOrder($this->name_order ?? NameOrderEnum::GivenFirst);

        $parts = [$this->prefix, $name];

        if ($this->maiden_name !== null && $this->maiden_name !== $name) {
            $parts[] = $this->maiden_name;
        }

        $parts[] = $this->suffix;

        return mb_trim(implode(' ', array_filter($parts)));
    }

    /**
     * Whether another contact of this vault has ever answered to the slug, or a route already fixes
     * it.
     *
     * Only the reservations are read. Every slug a contact is given is reserved in the same save,
     * so the reservations are a superset of what the contacts themselves carry, and a name released
     * by a correction stays spent. A contact does not collide with their own past: correcting a
     * name back to what it was gives them the same address again, which points at the person it
     * always pointed at.
     */
    private function slugIsSpent(string $slug): bool
    {
        if (in_array($slug, ReservedAddressWords::forContact(), true)) {
            return true;
        }

        return ContactSlugReservation::query()
            ->where('vault_id', $this->vault_id)
            ->where('slug', $slug)
            ->when($this->id !== null, fn (Builder $query) => $query->where('contact_id', '!=', $this->id))
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'name_order' => NameOrderEnum::class,
            'sort_key' => CollationKey::class,
            'is_me' => 'boolean',
            'is_partial' => 'boolean',
            'is_archived' => 'boolean',
            'is_favourite' => 'boolean',
            'last_life_event_at' => 'datetime',
            'last_interaction_at' => 'datetime',
            'life_event_count' => 'integer',
            'last_consulted_at' => 'datetime',
            'deleted_with_vault' => 'boolean',
        ];
    }

    /**
     * Whether the contact can be reached down the address that names it.
     *
     * A contact is addressed under the vault it lives in, and neither half can
     * be trusted on its own: the account has to match, and the person has to be
     * in the vault the URL actually names. Without the second half, the address
     * of one vault would open somebody kept in another.
     */
    public function isReachableThrough(User $user, Vault $vault): bool
    {
        return $this->account_id === $user->account_id
            && $this->vault_id === $vault->id;
    }

    /**
     * Get the account the contact belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the contact lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the exact structure version the page renders from. It is a version
     * and never a template, so the page keeps looking the way it did until
     * somebody deliberately moves the contact.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function structureVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class);
    }

    /**
     * Get the uploaded avatar, which is null when the contact is shown as the
     * initials the product draws instead.
     *
     * @return BelongsTo<File, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(File::class, 'avatar_file_id');
    }

    /**
     * Get every way the current name is written, in the order the owner put them.
     *
     * @return HasMany<ContactNameRepresentation, $this>
     */
    public function nameRepresentations(): HasMany
    {
        return $this->hasMany(ContactNameRepresentation::class)->chaperone()->orderBy('position')->orderBy('id');
    }

    /**
     * Get every group membership of the contact, current and former.
     *
     * @return HasMany<GroupMember, $this>
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get the loans of possessions this contact has had or lent, newest first.
     *
     * @return HasMany<PossessionLoan, $this>
     */
    public function possessionLoans(): HasMany
    {
        return $this->hasMany(PossessionLoan::class)
            ->orderByDesc('started_on')
            ->orderByDesc('id');
    }

    /**
     * Get the representation the contact is listed, titled and drawn under.
     *
     * @return BelongsTo<ContactNameRepresentation, $this>
     */
    public function displayNameRepresentation(): BelongsTo
    {
        return $this->belongsTo(ContactNameRepresentation::class, 'display_name_representation_id');
    }

    /**
     * The first representation in the given script, of the given kind, or both. Null when the
     * contact holds none, since nothing is ever transliterated on the fly.
     */
    public function nameFor(?string $script = null, ?ContactNameKindEnum $kind = null): ?ContactNameRepresentation
    {
        return $this->nameRepresentations
            ->when($script !== null, fn (Collection $representations): Collection => $representations->where('script_code', $script))
            ->when($kind instanceof ContactNameKindEnum, fn (Collection $representations): Collection => $representations->where('kind', $kind))
            ->first();
    }

    /**
     * Copy the display representation into the columns every list reads, or empty them when the
     * contact is left with no name. These columns are written here and nowhere else.
     */
    public function copyDisplayName(?ContactNameRepresentation $representation): void
    {
        $this->isCopyingTheDisplayName = true;
        $this->prefix = $representation?->prefix;
        $this->first_name = $representation?->given_name;
        $this->middle_name = $representation?->additional_name;
        $this->last_name = $representation?->family_name;
        $this->suffix = $representation?->suffix;
        $this->name_order = $representation?->name_order;
        $this->formatted_name = $representation?->formatted_name;
    }

    /**
     * Whether there is a picture to draw instead of the initials.
     */
    public function hasAvatar(): bool
    {
        return $this->avatar_file_id !== null;
    }

    /**
     * Get everything written on the page of the contact.
     *
     * @return MorphMany<FieldValue, $this>
     */
    public function fieldValues(): MorphMany
    {
        return $this->morphMany(FieldValue::class, 'valuable');
    }

    /**
     * Get the main birthday of the contact: the attached value of the birthday field, outside any repeated row.
     *
     * @return MorphOne<FieldValue, $this>
     */
    public function birthday(): MorphOne
    {
        return $this->morphOne(FieldValue::class, 'valuable')
            ->attached()
            ->where('field_key', DefaultFieldEnum::Birthday->value)
            ->whereNull('field_value_row_id');
    }

    /**
     * Get the repetitions of every repeater on the page of the contact.
     *
     * @return MorphMany<FieldValueRow, $this>
     */
    public function fieldValueRows(): MorphMany
    {
        return $this->morphMany(FieldValueRow::class, 'valuable');
    }

    /**
     * Get the life events the contact took part in. Being named in a narrative
     * does not count: only participation puts a life event here.
     *
     * @return MorphToMany<LifeEvent, $this>
     */
    public function lifeEvents(): MorphToMany
    {
        return $this->morphToMany(LifeEvent::class, 'participant', 'life_event_participants')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Get the states of the contact kept so they can be restored, newest first.
     *
     * @return MorphMany<RecordSnapshot, $this>
     */
    public function snapshots(): MorphMany
    {
        return $this->morphMany(RecordSnapshot::class, 'snapshotable')->latest();
    }

    /**
     * Get the private links opened onto the page of the contact, newest first.
     * A revoked one stays here, since what a link was given to and when it was
     * opened outlives the link itself.
     *
     * @return MorphMany<ShareLink, $this>
     */
    public function shareLinks(): MorphMany
    {
        return $this->morphMany(ShareLink::class, 'shareable')->latest();
    }

    /**
     * Get every reminder rule about this contact, quiet ones included. A rule
     * that has gone quiet stays here and says why, rather than disappearing.
     *
     * @return MorphMany<Reminder, $this>
     */
    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }

    /**
     * Get the slugs the contact has carried. Correcting a name releases the
     * slug it built, and this is what stops it being given to somebody else in
     * the vault, so an address saved before the correction fails rather than
     * opening the wrong person.
     *
     * @return HasMany<ContactSlugReservation, $this>
     */
    public function slugReservations(): HasMany
    {
        return $this->hasMany(ContactSlugReservation::class);
    }

    /**
     * Send the search engine again every record whose document holds the contact's name: the life
     * events the contact took part in, the people related to the contact, and the records whose
     * relation field points at the contact.
     */
    public function resendTheRecordsNamingIt(): void
    {
        LifeEvent::sendToSearch(LifeEvent::query()->whereKey($this->lifeEvents()->pluck('life_events.id')));

        $relatedIds = Relationship::query()
            ->forContact($this)
            ->get(['contact_a_id', 'contact_b_id'])
            ->map(fn (Relationship $relationship): int => $relationship->contact_a_id === $this->id ? $relationship->contact_b_id : $relationship->contact_a_id)
            ->all();

        $pointing = FieldValue::query()
            ->where('related_contact_id', $this->id)
            ->get(['valuable_type', 'valuable_id']);

        foreach ([self::class, Entity::class, LifeEvent::class] as $type) {
            $ids = $pointing->where('valuable_type', $type)->pluck('valuable_id')->all();

            if ($type === self::class) {
                $ids = [...$ids, ...$relatedIds];
            }

            if ($ids !== []) {
                $type::sendToSearch($type::query()->whereKey(array_unique($ids)));
            }
        }
    }

    /** @return list<string> */
    public function searchableRelations(): array
    {
        return ['nameRepresentations'];
    }

    /**
     * Every name the contact is found under, the one it is shown under first, one to a line: 김민지,
     * Kim Min-ji and 金敏智 all find the same person.
     */
    #[Override]
    public function searchableName(): string
    {
        return $this->nameRepresentations
            ->toBase()
            ->map(fn (ContactNameRepresentation $representation): string => $representation->displayText())
            ->prepend($this->name)
            ->filter()
            ->unique()
            ->implode("\n");
    }

    /**
     * The people the contact has a relationship with, each with how the contact relates to them,
     * read from the contact's side: Michael is the partner of Jan.
     *
     * @return list<array{name: string, label: string}>
     */
    #[Override]
    public function searchableLinkedNames(): array
    {
        $linked = [];

        $relationships = Relationship::query()
            ->forContact($this)
            ->with(['relationshipType', 'contactA.nameRepresentations', 'contactB.nameRepresentations'])
            ->get();

        foreach ($relationships as $relationship) {
            $isA = $relationship->contact_a_id === $this->id;
            $other = $isA ? $relationship->contactB : $relationship->contactA;

            if ($other === null) {
                continue;
            }

            $role = $isA ? $relationship->relationshipType->role_a : $relationship->relationshipType->role_b;

            $linked[] = ['name' => $other->searchableName(), 'label' => __(':role of :name', ['role' => $role, 'name' => $other->name])];
        }

        return $linked;
    }

    /**
     * Scope the query to the contacts the main lists show: the ones that are
     * neither archived nor still waiting to be filled in.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->where('is_archived', false)->where('is_partial', false);
    }

    /**
     * Scope the query to the people one chip of the contacts list shows.
     *
     * Somebody still waiting to be filled in is only in the partial grouping, and
     * somebody archived is in none of them, since nothing offers a way to archive
     * or to bring an archived contact back yet.
     *
     * This lives here rather than on the screen that first needed it because the
     * API answers the same groupings, and a narrowing kept in a view model
     * is one the two surfaces would sooner or later disagree about.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeInGrouping(Builder $query, ContactGroupingEnum $grouping): void
    {
        $query->where('is_partial', $grouping === ContactGroupingEnum::NamesOnly)
            ->where('is_archived', false)
            ->when(
                $grouping === ContactGroupingEnum::Favourites,
                fn (Builder $query): Builder => $query->where('is_favourite', true),
            );
    }

    /**
     * Get the categories the contact holds, in the order of the account's list.
     *
     * A category is a choice of the account's "Contact categories" list, held in any multiple
     * choice field of the structure drawing on that list. A value whose field has left the
     * structure is set aside and counts for nothing, and so is a value in a sensitive field for a
     * caller that does not reach sensitive fields.
     *
     * @return Collection<int, ValueListItem>
     */
    public function heldCategories(bool $reachesSensitiveFields): Collection
    {
        $categories = ValueList::query()
            ->where('account_id', $this->account_id)
            ->where('key', ValueListEnum::ContactCategories->value)
            ->select('id');

        $itemIds = FieldValue::query()
            ->where('valuable_type', $this->getMorphClass())
            ->where('valuable_id', $this->id)
            ->where('is_detached', false)
            ->whereIn('field_id', Field::query()
                ->whereIn('value_list_id', $categories)
                ->when(! $reachesSensitiveFields, fn (Builder $fields): Builder => $fields->where('is_sensitive', false))
                ->select('id'))
            ->pluck('value_list_item_ids')
            ->flatten()
            ->map(intval(...))
            ->unique();

        return ValueListItem::query()
            ->whereIn('id', $itemIds)
            ->orderBy('position')
            ->get();
    }

    /**
     * Scope the query to the people holding any of the given categories.
     *
     * A category is a choice of the account's "Contact categories" list, held in whichever
     * multiple choice field of the person's structure draws on that list. A value whose field
     * has left the structure is set aside, so it no longer files the person anywhere. For a
     * caller that does not reach sensitive fields, neither does a value in a sensitive field.
     *
     * @param  Builder<$this>  $query
     * @param  array<int, int>  $itemIds
     */
    public function scopeHoldingAnyCategory(Builder $query, array $itemIds, bool $reachesSensitiveFields): void
    {
        $query->whereExists(fn (QueryBuilder $values): QueryBuilder => $values
            ->from('field_values')
            ->whereColumn('field_values.valuable_id', 'contacts.id')
            ->where('field_values.valuable_type', $this->getMorphClass())
            ->where('field_values.is_detached', false)
            ->when(! $reachesSensitiveFields, fn (QueryBuilder $chosen): QueryBuilder => $chosen
                ->whereNotIn('field_values.field_id', Field::query()->where('is_sensitive', true)->select('id')))
            ->where(function (QueryBuilder $chosen) use ($itemIds): void {
                foreach ($itemIds as $itemId) {
                    $chosen->orWhereJsonContains('field_values.value_list_item_ids', $itemId);
                }
            }));
    }

    /**
     * Scope the query to the people a search term reaches.
     *
     * The term meets `search_key` folded for the language given, minus what that
     * language calls a minor difference. French drops the accent on an e, so
     * elodie finds Élodie; Spanish keeps its tilde and Turkish its dotless i, so
     * nunez does not find Ñuñez. It is matched byte for byte, and `sort_name` is
     * not matched at all, because a database has its own opinion: MySQL's
     * `utf8mb4_unicode_ci` has no Spanish tailoring and calls n and ñ the same,
     * which would undo all of the above.
     *
     * The term is handed to the fold as it was typed rather than lowercased on
     * the way, because the fold lowercases with that language's own rules, and
     * Turkish turns I into a dotless i that `mb_strtolower` never produces. The
     * nickname is stored as somebody typed it and never folded, so it is matched
     * case insensitively against the plainly lowercased term instead.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeMatchingSearch(Builder $query, string $term, ?string $locale = null): void
    {
        $term = mb_trim($term);

        if ($term === '') {
            return;
        }

        $folded = LocaleCollation::foldForSearch($term, $locale);
        $lowered = mb_strtolower($term);

        $query->where(function (Builder $query) use ($lowered, $folded): void {
            $query->whereLike('search_key', "%{$folded}%", caseSensitive: true)
                ->orWhereLike('nickname', "%{$lowered}%", caseSensitive: false)
                ->orWhereExists(function (QueryBuilder $names) use ($folded): void {
                    $names->selectRaw('1')
                        ->from('contact_name_representations')
                        ->whereColumn('contact_name_representations.contact_id', 'contacts.id')
                        ->whereLike('contact_name_representations.search_key', "%{$folded}%", caseSensitive: true);
                });
        });
    }

    /**
     * Scope the query to the contact's children: the people a parentage
     * relationship reaches with the given contact on the parent side.
     *
     * Status is not consulted. Ending contact with a child does not undo the
     * parentage, which is the same rule the family tree reads by.
     *
     * This lives here rather than on the screen that first needed it because
     * the API answers the same question, and a selection kept in a view model
     * is one the two surfaces would sooner or later disagree about.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeChildrenOf(Builder $query, self $contact): void
    {
        $query->where('account_id', $contact->account_id)
            ->where('vault_id', $contact->vault_id)
            ->whereIn('id', function (QueryBuilder $rows) use ($contact): void {
                $rows->select('contact_b_id')
                    ->from('relationships')
                    ->whereNull('deleted_at')
                    ->where('vault_id', $contact->vault_id)
                    ->where('contact_a_id', $contact->id)
                    ->whereIn('relationship_type_id', self::typesBehavingAs(
                        RelationshipBehaviorEnum::GenealogyParentage,
                        $contact->account_id,
                    ));
            });
    }

    /**
     * Scope the query to the contact's parents: the people a parentage
     * relationship reaches with the given contact on the child side.
     *
     * The inverse of `childrenOf()`, and it reads by the same rules. Status is
     * not consulted, because ending contact with a parent does not undo the
     * parentage, and membership is read off the behaviour so that renaming the
     * type, moving it or withdrawing it changes nobody.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeParentsOf(Builder $query, self $contact): void
    {
        $query->where('account_id', $contact->account_id)
            ->where('vault_id', $contact->vault_id)
            ->whereIn('id', function (QueryBuilder $rows) use ($contact): void {
                $rows->select('contact_a_id')
                    ->from('relationships')
                    ->whereNull('deleted_at')
                    ->where('vault_id', $contact->vault_id)
                    ->where('contact_b_id', $contact->id)
                    ->whereIn('relationship_type_id', self::typesBehavingAs(
                        RelationshipBehaviorEnum::GenealogyParentage,
                        $contact->account_id,
                    ));
            });
    }

    /**
     * Scope the query to whoever the contact is with now: the people a romantic
     * relationship recorded as current reaches, from either side.
     *
     * Both sides are asked separately so each meets an index of its own. A pair
     * linked several times over the years answers once, because the people are
     * selected by id rather than the rows counted.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeCurrentPartnersOf(Builder $query, self $contact): void
    {
        $query->where('account_id', $contact->account_id)
            ->where('vault_id', $contact->vault_id)
            ->where(function (Builder $query) use ($contact): void {
                $query->whereIn('id', function (QueryBuilder $rows) use ($contact): void {
                    $rows->select('contact_b_id')
                        ->from('relationships')
                        ->whereNull('deleted_at')
                        ->where('vault_id', $contact->vault_id)
                        ->where('status', RelationshipStatusEnum::Current->value)
                        ->where('contact_a_id', $contact->id)
                        ->whereIn('relationship_type_id', self::typesBehavingAs(
                            RelationshipBehaviorEnum::Romantic,
                            $contact->account_id,
                        ));
                })->orWhereIn('id', function (QueryBuilder $rows) use ($contact): void {
                    $rows->select('contact_a_id')
                        ->from('relationships')
                        ->whereNull('deleted_at')
                        ->where('vault_id', $contact->vault_id)
                        ->where('status', RelationshipStatusEnum::Current->value)
                        ->where('contact_b_id', $contact->id)
                        ->whereIn('relationship_type_id', self::typesBehavingAs(
                            RelationshipBehaviorEnum::Romantic,
                            $contact->account_id,
                        ));
                });
            });
    }

    /**
     * The types carrying one of the protected behaviours, as a subquery.
     *
     * Membership is read off the behaviour and never off a name, a key or a
     * category, so renaming Spouse, moving it or withdrawing it leaves the
     * people it linked exactly where they are, and a type somebody invents and
     * also calls Spouse reaches nobody.
     *
     * @return Builder<RelationshipType>
     */
    private static function typesBehavingAs(RelationshipBehaviorEnum $behavior, int $accountId): Builder
    {
        return RelationshipType::query()
            ->select('id')
            ->where('account_id', $accountId)
            ->where('system_behavior', $behavior);
    }

    /**
     * Order the query the way the contacts list is ordered.
     *
     * A column that can be unknown is sorted on twice, the unknowns pushed to
     * the end first. Postgres ranks null above everything and MySQL and SQLite
     * rank it below, so saying it here is what keeps the list reading the same
     * on every installation.
     *
     * The order always ends on the primary key, or two people added in the same
     * second land in whatever order the database felt like and a row appears on
     * two pages or on none.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSortedBy(Builder $query, ContactSortEnum $sort, SortDirectionEnum $direction): void
    {
        $column = $sort->column();

        $query->when(
            $sort->canBeUnknown(),
            fn (Builder $query): Builder => $query->orderByRaw("{$column} is null"),
        )
            ->orderBy($column, $direction->value)
            ->orderBy('id');
    }

    /**
     * Get the name the contact is written as. A contact may read in its own
     * order, which is what lets a Japanese name keep its order in a list of
     * western ones; when it names none, the given name comes first and the
     * screen is free to apply the user's preference instead.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $name = $this->nameInOrder($this->name_order ?? NameOrderEnum::GivenFirst);

                if ($name !== '') {
                    return $name;
                }

                return __('Unnamed contact');
            },
        );
    }

    public function isNameless(): bool
    {
        return $this->nameInOrder($this->name_order ?? NameOrderEnum::GivenFirst) === '';
    }

    /**
     * Get the letters drawn on the avatar when there is no photo to draw
     * instead. They are the given and the family name, in that order whatever
     * order the name reads in, and a contact known by a nickname alone still
     * gets a letter of their own.
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->isNameless()) {
                    return '?';
                }

                $words = collect([$this->first_name, $this->last_name])->filter();

                if ($words->isEmpty()) {
                    $words = collect([$this->name]);
                }

                return $words
                    ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
                    ->implode('');
            },
        );
    }

    /**
     * Get the name the contact is written as, in the order asked for. The
     * middle name sits with the given name, since it is one.
     */
    public function nameInOrder(NameOrderEnum $order): string
    {
        $formatted = mb_trim((string) $this->formatted_name);

        if ($formatted !== '') {
            return $formatted;
        }

        $given = mb_trim("{$this->first_name} {$this->middle_name}");
        $name = $order->format($given, (string) $this->last_name);

        if ($name !== '') {
            return $name;
        }

        return (string) ($this->nickname ?? $this->maiden_name);
    }

    /**
     * Build the string the lists sort on, by the filing rule of the language
     * given: one rule for every row, whatever order each contact reads in,
     * because a list sorted one way for some rows and another way for others
     * is sorted no way at all. Where the language orders names by how they are
     * read, the first pronunciation written in a script it reads by stands in
     * for the name. It is lowercased in that language, so the order
     * does not depend on the collation of the database underneath. A contact
     * with no name part at all sorts on an empty string, which is what lets
     * somebody be written down before their name is known.
     */
    public function sortNameFor(string $locale): string
    {
        $scripts = NameFiling::phoneticScripts($locale);

        $pronunciation = $scripts === [] || ! $this->exists
            ? null
            : $this->nameRepresentations->first(fn (ContactNameRepresentation $representation): bool => $representation->kind === ContactNameKindEnum::Phonetic
                && in_array($representation->script_code, $scripts, true));

        if ($pronunciation !== null) {
            return NameFiling::sortName(
                locale: $locale,
                family: $pronunciation->family_name,
                given: $pronunciation->given_name,
                additional: $pronunciation->additional_name,
                fallback: (string) $pronunciation->formatted_name,
            );
        }

        return NameFiling::sortName(
            locale: $locale,
            family: $this->last_name,
            given: $this->first_name,
            additional: $this->middle_name,
            fallback: (string) ($this->formatted_name ?? $this->nickname ?? $this->maiden_name),
        );
    }

    /**
     * Build the text a search matches against: the name family first, folded
     * in the language given. It does not follow the filing rule, so a search
     * finds exactly what it found before that rule existed.
     */
    public function searchKeyFor(string $locale): string
    {
        $parts = collect([$this->last_name, $this->first_name, $this->middle_name])
            ->filter()
            ->implode(' ');

        $text = mb_trim($parts !== '' ? $parts : (string) ($this->formatted_name ?? $this->nickname ?? $this->maiden_name));

        return LocaleCollation::foldForSearch(mb_substr(mb_strtolower($text), 0, 511), $locale);
    }

    /**
     * The one part of the name detection groups contacts by: the family name where there is one.
     */
    private function buildMatchToken(): string
    {
        return mb_trim((string) ($this->last_name
            ?? $this->first_name
            ?? $this->formatted_name
            ?? $this->nickname
            ?? $this->maiden_name));
    }
}
