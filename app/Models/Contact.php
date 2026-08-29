<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NameOrderEnum;
use App\Exceptions\ContactNeedsAName;
use App\Helpers\LocaleCollation;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * Class Contact
 *
 * A person being documented, and the object the whole product orbits.
 *
 * The concept is frozen and its content is not. A contact will always be a
 * person with a name; everything else on the page comes from the template
 * version it points at. Only the name is imposed, because the product uses it
 * constantly to sort, to search, to render headers and to build initials, and
 * none of that can depend on a field the user might have deleted.
 *
 * Two special states live here. One contact per vault is flagged `is_me` and
 * represents the user, so that "Alice is my sister" is a relationship between
 * two rows rather than an invisible parallel logic. A partial contact is
 * somebody mentioned in a relationship and reduced to a first name, kept out of
 * the main lists until they are filled in.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $template_version_id
 * @property string|null $prefix
 * @property string|null $first_name
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $maiden_name
 * @property string|null $nickname
 * @property NameOrderEnum|null $name_order
 * @property string $sort_name
 * @property string $sort_key
 * @property string $search_key
 * @property bool $is_me
 * @property bool $is_partial
 * @property bool $is_archived
 * @property bool $is_favourite
 * @property int|null $avatar_file_id
 * @property string $avatar_colour
 * @property CarbonImmutable|null $last_activity_at
 * @property int $activity_count
 * @property CarbonImmutable|null $last_consulted_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property bool $deleted_with_vault
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read TemplateVersion $templateVersion
 * @property-read File|null $avatar
 * @property-read string $name
 * @property-read string $initials
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'template_version_id',
    'prefix',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'maiden_name',
    'nickname',
    'name_order',
    'is_me',
    'is_partial',
    'is_archived',
    'is_favourite',
    'avatar_file_id',
    'avatar_colour',
    'last_activity_at',
    'activity_count',
    'last_consulted_at',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SoftDeletes;

    /**
     * The side of the square an uploaded avatar is cropped to. The biggest
     * place it is drawn is the header of the record at 104, so this stays sharp
     * on a dense screen while a list of forty people costs a couple of
     * megabytes rather than a couple of hundred.
     */
    public const int AVATAR_PIXELS = 512;

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
     * Write the string the lists sort on and the two keys derived from it, here
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
            $contact->sort_name = $contact->buildSortName();

            $locale = $contact->account->locale();
            $contact->sort_key = LocaleCollation::sortKey($contact->sort_name, $locale);
            $contact->search_key = LocaleCollation::foldForSearch($contact->sort_name, $locale);
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
            'is_me' => 'boolean',
            'is_partial' => 'boolean',
            'is_archived' => 'boolean',
            'is_favourite' => 'boolean',
            'last_activity_at' => 'datetime',
            'activity_count' => 'integer',
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
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
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
     * Get the repetitions of every repeater on the page of the contact.
     *
     * @return MorphMany<FieldValueRow, $this>
     */
    public function fieldValueRows(): MorphMany
    {
        return $this->morphMany(FieldValueRow::class, 'valuable');
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
            get: fn (): string => $this->nameInOrder($this->name_order ?? NameOrderEnum::GivenFirst),
        );
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
        $given = mb_trim("{$this->first_name} {$this->middle_name}");
        $name = $order->format($given, (string) $this->last_name);

        if ($name !== '') {
            return $name;
        }

        return (string) ($this->nickname ?? $this->maiden_name);
    }

    /**
     * Build the string the lists sort on. It reads family name first whatever
     * the contact reads as, because a list sorted one way for some rows and
     * another way for others is sorted no way at all, and it is lowercased so
     * the order does not depend on the collation of the database underneath.
     */
    private function buildSortName(): string
    {
        $parts = collect([$this->last_name, $this->first_name, $this->middle_name])
            ->filter()
            ->implode(' ');

        $sortName = mb_trim($parts !== '' ? $parts : (string) ($this->nickname ?? $this->maiden_name));

        if ($sortName === '') {
            throw new ContactNeedsAName('A contact needs at least one part of a name.');
        }

        return mb_substr(mb_strtolower($sortName), 0, 511);
    }
}
