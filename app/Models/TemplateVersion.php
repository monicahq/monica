<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class TemplateVersion
 *
 * One frozen state of a template, and an ordered assembly of section versions.
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
 * @property int $template_id
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
 * @property-read Template $template
 * @property-read TemplateVersion|null $previousVersion
 */
#[Fillable([
    'account_id',
    'template_id',
    'version_number',
    'status',
    'change_note',
    'published_at',
    'retired_at',
    'previous_version_id',
    'record_count',
])]
class TemplateVersion extends Model
{
    /** @use HasFactory<TemplateVersionFactory> */
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
        'template_id',
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
        static::updating(function (TemplateVersion $version): void {
            if ($version->getRawOriginal('status') !== VersionStatusEnum::Published->value) {
                return;
            }

            if ($version->isDirty(self::FROZEN)) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} has been published. Make a draft from it instead.");
            }
        });

        static::deleting(function (TemplateVersion $version): void {
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
     * Get the account the version belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the template this is a version of.
     *
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the version this one was made from, which is null for the first.
     *
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class, 'previous_version_id');
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
     * @return HasMany<TemplateVersionPage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(TemplateVersionPage::class)->orderBy('position');
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
     * @return HasMany<TemplateVersionSection, $this>
     */
    public function assembly(): HasMany
    {
        return $this->hasMany(TemplateVersionSection::class)
            ->with([
                'sectionVersion.section',
                'sectionVersion.fields.valueList.items',
                'sectionVersion.fields.children.valueList.items',
            ])
            ->orderBy('template_version_page_id')
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
        return $this->belongsToMany(SectionVersion::class, 'template_version_sections')
            ->withPivot(['position', 'title_override', 'is_collapsed_by_default'])
            ->orderBy('position');
    }
}
