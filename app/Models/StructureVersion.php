<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\StructureVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class StructureVersion
 *
 * One frozen state of a structure, and an ordered assembly of section versions.
 *
 * Two rules govern it. A published version never changes again, ever, and any
 * edit produces a new version beside it with its own change note. A record
 * points at a precise version and follows it until somebody deliberately moves
 * it, which takes a snapshot first.
 *
 * A version stays freely editable while it is a draft, because freezing at
 * every keystroke during design would produce hundreds of versions nobody asked
 * for. Freezing happens at publication, and the model refuses the write from
 * that moment on rather than trusting every caller to remember.
 *
 * @property int $id
 * @property int $account_id
 * @property int $structure_id
 * @property int $version_number
 * @property VersionStatusEnum $status
 * @property string|null $change_note
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $retired_at
 * @property int|null $previous_version_id
 * @property int $record_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Structure $structure
 * @property-read StructureVersion|null $previousVersion
 */
#[Fillable([
    'account_id',
    'structure_id',
    'version_number',
    'status',
    'change_note',
    'published_at',
    'retired_at',
    'previous_version_id',
    'record_count',
])]
class StructureVersion extends Model
{
    /** @use HasFactory<StructureVersionFactory> */
    use HasFactory;

    /**
     * What publication freezes. Everything else on the row describes where the
     * version stands rather than what it contains: it may still be retired, and
     * the count of records pointing at it still moves.
     *
     * @var list<string>
     */
    private const array FROZEN = [
        'account_id',
        'structure_id',
        'version_number',
        'change_note',
        'published_at',
        'previous_version_id',
    ];

    /**
     * Guard the freeze here rather than in each action that could reach it. The
     * promise that a record keeps rendering the way it did is only worth
     * something if no code path at all can edit a published version, and there
     * is one place where every path meets.
     */
    #[Override]
    protected static function booted(): void
    {
        static::updating(function (StructureVersion $version): void {
            if ($version->getRawOriginal('status') !== VersionStatusEnum::Published->value) {
                return;
            }

            if ($version->isDirty(self::FROZEN)) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} has been published. Make a draft from it instead.");
            }
        });

        static::deleting(function (StructureVersion $version): void {
            if ($version->status !== VersionStatusEnum::Draft) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} has been published and cannot be deleted. Retire it instead.");
            }

            if ($version->record_count > 0) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} still holds {$version->record_count} records.");
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
            'version_number' => 'integer',
            'status' => VersionStatusEnum::class,
            'published_at' => 'datetime',
            'retired_at' => 'datetime',
            'record_count' => 'integer',
        ];
    }

    /**
     * Whether this version is one the given user may reach through the given
     * owner, which is a template or an activity type.
     *
     * A version is addressed under the thing it belongs to, and neither half can
     * be trusted on its own: the account has to match, and the version has to be
     * one of the structure the owner in the URL actually owns.
     */
    public function isReachableThrough(User $user, ActivityType|Template $owner): bool
    {
        return $this->account_id === $user->account_id
            && $this->structure->owner_type === $owner::class
            && $this->structure->owner_id === $owner->id;
    }

    /**
     * Get the account the version belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the structure this is a version of.
     *
     * The version names a structure and never its owner. That is the whole
     * point of the separation: a template and an activity type both own one,
     * and nothing in the engine has to know which of the two it is looking at.
     *
     * @return BelongsTo<Structure, $this>
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /**
     * Get the version this one was made from, which is null for the first.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class, 'previous_version_id');
    }

    /**
     * Get the contacts rendering from this version. They stay on it until
     * somebody deliberately moves them, and `record_count` is what says how
     * many there are without counting them.
     *
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the pages this version is read in, in the order the tabs appear.
     * Every version has at least one.
     *
     * @return HasMany<StructureVersionPage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(StructureVersionPage::class)->orderBy('position');
    }

    /**
     * Get the assembly: which section versions this version carries.
     *
     * A position is a place on a page and starts again on each of them, so the
     * rows are grouped by page before being ordered. Reading a version in the
     * order somebody sees it means walking {@see pages()} and asking each page
     * for its own sections; this relation is for everything that has to touch
     * every row, whichever tab it sits on.
     *
     * It brings the whole structure with it: the section version, its section,
     * its fields, the fields nested inside a repeatable one, and the value list
     * each choice field offers. A row of the assembly says which section
     * version sits where and nothing about what it holds, so every reader asked
     * for the rest one row at a time, which cost a query per section and two
     * more per choice field on the record screen, the busiest read in the
     * application. The cost of loading it here is the items of every list the
     * structure names, whether or not the screen draws them. Anything that
     * wants the rows alone and not their contents counts them with withCount()
     * rather than loading them.
     *
     * @return HasMany<StructureVersionSection, $this>
     */
    public function assembly(): HasMany
    {
        return $this->hasMany(StructureVersionSection::class)
            ->with([
                'sectionVersion.section',
                'sectionVersion.fields.valueList.items',
                'sectionVersion.fields.children.valueList.items',
            ])
            ->orderBy('structure_version_page_id')
            ->orderBy('position');
    }

    /**
     * Get the section versions this version carries. The relation goes to the
     * versions and never to the sections themselves, which is what makes the
     * freeze cascade all the way down to the fields.
     *
     * @return BelongsToMany<SectionVersion, $this>
     */
    public function sectionVersions(): BelongsToMany
    {
        return $this->belongsToMany(SectionVersion::class, 'structure_version_sections')
            ->withPivot(['position', 'title_override', 'is_collapsed_by_default'])
            ->orderBy('position');
    }
}
