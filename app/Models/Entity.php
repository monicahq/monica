<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\CollationKey;
use App\Exceptions\EntityNeedsAName;
use App\Helpers\LocaleCollation;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use App\Models\Concerns\SearchesRecords;
use Carbon\CarbonImmutable;
use Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class Entity
 *
 * One record of a type the user invented: a specific dog, a specific company, a
 * specific bottle. It sits beside contacts rather than inside them.
 *
 * An entity is thinner than a contact because almost nothing about it is
 * imposed. It has a name so it can be listed and searched, and everything else
 * comes from its template. It has no structured name parts, because it is not
 * necessarily a person.
 *
 * It does not take part in the human relationship system. That system carries
 * direction, periods, uncertainty, qualifiers and deduction, none of which make
 * sense for a bottle of wine; the link between somebody and their dog is an
 * ordinary relation field.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $entity_type_id
 * @property int $structure_version_id
 * @property string $name
 * @property string $sort_name
 * @property string $sort_key
 * @property string $search_key
 * @property int|null $avatar_file_id
 * @property string $avatar_colour
 * @property bool $is_archived
 * @property CarbonImmutable|null $last_consulted_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property bool $deleted_with_vault
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read EntityType $entityType
 * @property-read StructureVersion $structureVersion
 * @property-read File|null $avatar
 * @property-read string $initials
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'entity_type_id',
    'structure_version_id',
    'name',
    'avatar_file_id',
    'avatar_colour',
    'is_archived',
    'last_consulted_at',
])]
#[Hidden([
    'search_name',
    'search_body',
    'search_sensitive_body',
    'search_linked_names',
])]
class Entity extends Model
{
    /** @use HasFactory<EntityFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SearchesRecords;
    use SoftDeletes;

    /**
     * The backgrounds a generated avatar is drawn on. They are picked from when
     * a record is created and kept afterwards, so somebody who has learnt to
     * spot a row in a list does not have it change colour.
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
     * Write the string the lists sort on, here rather than in the actions, for
     * the reason a contact does it here too: the order has to be the same
     * whoever is looking, and every path that writes a name meets in this one
     * place.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (Entity $entity): void {
            $entity->sort_name = $entity->buildSortName();

            $locale = $entity->account->locale();
            $entity->sort_key = LocaleCollation::sortKey($entity->sort_name, $locale);
            $entity->search_key = LocaleCollation::foldForSearch($entity->sort_name, $locale);
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
            'sort_key' => CollationKey::class,
            'is_archived' => 'boolean',
            'last_consulted_at' => 'datetime',
            'deleted_with_vault' => 'boolean',
        ];
    }

    /**
     * Whether the entity can be reached down the address that names it.
     *
     * A record is addressed under the vault it lives in, and neither half can
     * be trusted on its own: the account has to match, and the record has to be
     * in the vault the URL actually names.
     */
    public function isReachableThrough(User $user, Vault $vault): bool
    {
        return $this->account_id === $user->account_id
            && $this->vault_id === $vault->id;
    }

    /**
     * Get the account the entity belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the entity lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the kind of object this is, which never changes.
     *
     * @return BelongsTo<EntityType, $this>
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * Get the exact structure version the page renders from. It is a version
     * and never a template, so the page keeps looking the way it did until
     * somebody deliberately moves the record.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function structureVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class);
    }

    /**
     * Get the image standing in for the record, which is null when it is drawn
     * as the initials the product generates instead.
     *
     * @return BelongsTo<File, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(File::class, 'avatar_file_id');
    }

    /**
     * Get everything written on the page of the record.
     *
     * @return MorphMany<FieldValue, $this>
     */
    public function fieldValues(): MorphMany
    {
        return $this->morphMany(FieldValue::class, 'valuable');
    }

    /**
     * Get the repetitions of every repeater on the page of the record.
     *
     * @return MorphMany<FieldValueRow, $this>
     */
    public function fieldValueRows(): MorphMany
    {
        return $this->morphMany(FieldValueRow::class, 'valuable');
    }

    /**
     * Get the states of the record kept so they can be restored, newest first.
     *
     * @return MorphMany<RecordSnapshot, $this>
     */
    public function snapshots(): MorphMany
    {
        return $this->morphMany(RecordSnapshot::class, 'snapshotable')->latest();
    }

    /**
     * The name the record is found and listed under.
     */
    #[Override]
    public function searchableName(): string
    {
        return $this->name;
    }

    /**
     * Nothing is linked to a record by name: relationships belong to contacts alone.
     *
     * @return list<array{name: string, label: string}>
     */
    #[Override]
    public function searchableLinkedNames(): array
    {
        return [];
    }

    /**
     * The entity type the record is grouped under in search results.
     */
    public function searchableEntityTypeId(): ?int
    {
        return $this->entity_type_id;
    }

    /**
     * Scope the query to the records the main lists show: the ones that are not
     * archived.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->where('is_archived', false);
    }

    /**
     * Scope the query to the records of the contact's vault that one of their relation fields names
     * the contact in, leaving out a value a change of template set aside.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeNaming(Builder $query, Contact $contact): void
    {
        $query->where('vault_id', $contact->vault_id)
            ->whereHas('fieldValues', function (Builder $query) use ($contact): void {
                $query->attached()->where('related_contact_id', $contact->id);
            });
    }

    /**
     * Scope the query to the records whose name holds the given term, folded
     * for the language it is read in, the same way a contact is searched. A
     * blank term leaves the query as it is.
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

        $query->whereLike('search_key', "%{$folded}%", caseSensitive: true);
    }

    /**
     * Get the letters drawn on the generated avatar. They are the first letter
     * of the first two words of the name, since an entity has no name parts to
     * pick from.
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: fn (): string => collect(preg_split('/\s+/', mb_trim($this->name)) ?: [])
                ->filter()
                ->take(2)
                ->map(fn (string $word): string => mb_strtoupper((string) grapheme_substr($word, 0, 1)))
                ->implode(''),
        );
    }

    /**
     * Build the string the lists sort on. It is lowercased so the order does
     * not depend on the collation of the database underneath, and a record left
     * with no name is refused here, which is the moment the question gets
     * asked at all.
     */
    private function buildSortName(): string
    {
        $sortName = mb_trim((string) $this->name);

        if ($sortName === '') {
            throw new EntityNeedsAName('An entity needs a name.');
        }

        return mb_substr(mb_strtolower($sortName), 0, 511);
    }
}
