<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\SectionVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class SectionVersion
 *
 * One frozen state of a section, and the thing that actually owns fields.
 * Everything a structure version promises about freezing applies here
 * identically, one level down: fields belong to the version, never to the
 * section, so adding a field to a published section means publishing a new
 * version of it.
 *
 * This is also where the freeze stops being a claim about this table alone.
 * A field that reads from a value list pins the state of that list at
 * publication, in `resolved_value_lists`, so that renaming an option somewhere
 * else in the product cannot change what a section published yesterday does.
 * Reading a stored value still resolves through the live item while it is still
 * there, so a relabelled option reads with its new label. The snapshot is what
 * answers when the item is gone.
 *
 * @property int $id
 * @property int $account_id
 * @property int $section_id
 * @property int $version_number
 * @property VersionStatusEnum $status
 * @property string|null $change_note
 * @property array<int, array<string, mixed>>|null $resolved_value_lists
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $retired_at
 * @property int|null $previous_version_id
 * @property int $usage_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Section $section
 * @property-read SectionVersion|null $previousVersion
 */
#[Fillable([
    'account_id',
    'section_id',
    'version_number',
    'status',
    'change_note',
    'resolved_value_lists',
    'published_at',
    'retired_at',
    'previous_version_id',
    'usage_count',
])]
class SectionVersion extends Model
{
    /** @use HasFactory<SectionVersionFactory> */
    use HasFactory;

    /**
     * What publication freezes. The count of structure versions containing it
     * still moves, and it may still be retired, but nothing that decides what
     * the section renders may change again.
     *
     * @var list<string>
     */
    private const array FROZEN = [
        'account_id',
        'section_id',
        'version_number',
        'change_note',
        'resolved_value_lists',
        'published_at',
        'previous_version_id',
    ];

    /**
     * Guard the freeze here rather than in each action that could reach it, for
     * the same reason a structure version does.
     */
    #[Override]
    protected static function booted(): void
    {
        static::updating(function (SectionVersion $version): void {
            if ($version->getRawOriginal('status') !== VersionStatusEnum::Published->value) {
                return;
            }

            if ($version->isDirty(self::FROZEN)) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} has been published. Make a draft from it instead.");
            }
        });

        static::deleting(function (SectionVersion $version): void {
            if ($version->status !== VersionStatusEnum::Draft) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} has been published and cannot be deleted. Retire it instead.");
            }

            if ($version->usage_count > 0) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} sits in {$version->usage_count} structures.");
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
            'resolved_value_lists' => 'array',
            'published_at' => 'datetime',
            'retired_at' => 'datetime',
            'usage_count' => 'integer',
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
     * Get the section this is a version of.
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the version this one was made from, which is null for the first.
     *
     * @return BelongsTo<SectionVersion, $this>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(SectionVersion::class, 'previous_version_id');
    }

    /**
     * Get what the section asks for, in the order it asks.
     *
     * @return HasMany<Field, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(Field::class)->orderBy('position');
    }

    /**
     * Get the structure versions this section sits in.
     *
     * @return BelongsToMany<StructureVersion, $this>
     */
    public function structureVersions(): BelongsToMany
    {
        return $this->belongsToMany(StructureVersion::class, 'structure_version_sections')
            ->withPivot(['position', 'title_override', 'is_collapsed_by_default']);
    }
}
