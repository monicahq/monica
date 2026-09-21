<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\StructureVersionSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class StructureVersionSection
 *
 * The assembly. It says which section versions a structure version contains, and
 * in which order.
 *
 * It is a pivot with attributes rather than a plain join, because the same
 * section can appear in several structures at different positions, under a
 * different title, folded in one and open in another. The title override is
 * display only and changes no identity: it exists so a shared section can be
 * reused without being duplicated just to rename its header.
 *
 * The foreign key points at a section version, never at a section. That is the
 * whole mechanism by which freezing a structure version freezes the fields the
 * record ends up showing.
 *
 * A placement may also hide the section's name. That is what carries a block of
 * fields nobody grouped: they are still a section, because a field belongs to a
 * section version and nothing else, but the reader is shown the fields alone
 * with no heading over them. It is display only, like the title override, and
 * the same section placed elsewhere keeps its name.
 *
 * @property int $id
 * @property int $account_id
 * @property int $structure_version_id
 * @property int $structure_version_page_id
 * @property int $section_version_id
 * @property int $position
 * @property string|null $title_override
 * @property bool $shows_title
 * @property bool $is_collapsed_by_default
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read StructureVersion $structureVersion
 * @property-read StructureVersionPage $structureVersionPage
 * @property-read SectionVersion $sectionVersion
 */
#[Fillable([
    'account_id',
    'structure_version_id',
    'structure_version_page_id',
    'section_version_id',
    'position',
    'title_override',
    'shows_title',
    'is_collapsed_by_default',
])]
class StructureVersionSection extends Model
{
    /** @use HasFactory<StructureVersionSectionFactory> */
    use HasFactory;

    /**
     * A row is as frozen as the version it belongs to. Once the structure version has been
     * published, nothing may be added to it, taken out of it or moved within
     * it, since all three would change what an already published version
     * renders.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (StructureVersionSection $row): void {
            $row->refuseWhenTheStructureIsPublished();
        });

        static::deleting(function (StructureVersionSection $row): void {
            $row->refuseWhenTheStructureIsPublished();
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
            'position' => 'integer',
            'shows_title' => 'boolean',
            'is_collapsed_by_default' => 'boolean',
        ];
    }

    /**
     * Whether this row is one the given user may reach through the given owner,
     * which is a template or a life event type.
     *
     * The rows are addressed by their own number in a URL, under the thing they
     * sit on, and neither half can be trusted on its own: the account has to
     * match, and the row has to be on a version of the structure the URL
     * actually names. Without the second half, somebody could put a field on
     * one of their templates while the URL said another.
     */
    public function isReachableThrough(User $user, LifeEventType|Template $owner): bool
    {
        return $this->account_id === $user->account_id
            && $this->structureVersion->structure->owner_type === $owner::class
            && $this->structureVersion->structure->owner_id === $owner->id;
    }

    /**
     * Get the account the assembly belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the structure version the section is placed in.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function structureVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class);
    }

    /**
     * Get the page of that version the section appears on.
     *
     * @return BelongsTo<StructureVersionPage, $this>
     */
    public function structureVersionPage(): BelongsTo
    {
        return $this->belongsTo(StructureVersionPage::class);
    }

    /**
     * Get the exact section that is placed.
     *
     * @return BelongsTo<SectionVersion, $this>
     */
    public function sectionVersion(): BelongsTo
    {
        return $this->belongsTo(SectionVersion::class);
    }

    private function refuseWhenTheStructureIsPublished(): void
    {
        $version = $this->structureVersion;

        if ($version !== null && ! $version->status->isEditable()) {
            throw new PublishedVersionIsFrozen("Version {$version->version_number} of this structure has been published. Make a draft from it instead.");
        }
    }
}
