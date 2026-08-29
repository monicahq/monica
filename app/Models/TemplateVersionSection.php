<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateVersionSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class TemplateVersionSection
 *
 * The assembly. It says which section versions a template version contains, and
 * in which order.
 *
 * It is a pivot with attributes rather than a plain join, because the same
 * section can appear in several templates at different positions, under a
 * different title, folded in one and open in another. The title override is
 * display only and changes no identity: it exists so a shared section can be
 * reused without being duplicated just to rename its header.
 *
 * The foreign key points at a section version, never at a section. That is the
 * whole mechanism by which freezing a template version freezes the fields the
 * record ends up showing.
 *
 * @property int $id
 * @property int $account_id
 * @property int $template_version_id
 * @property int $template_version_page_id
 * @property int $section_version_id
 * @property int $position
 * @property string|null $title_override
 * @property bool $is_collapsed_by_default
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read TemplateVersion $templateVersion
 * @property-read TemplateVersionPage $templateVersionPage
 * @property-read SectionVersion $sectionVersion
 */
#[Fillable([
    'account_id',
    'template_version_id',
    'template_version_page_id',
    'section_version_id',
    'position',
    'title_override',
    'is_collapsed_by_default',
])]
class TemplateVersionSection extends Model
{
    /** @use HasFactory<TemplateVersionSectionFactory> */
    use HasFactory;

    /**
     * A row is as frozen as the version it belongs to. Once the template version has been
     * published, nothing may be added to it, taken out of it or moved within
     * it, since all three would change what an already published version
     * renders.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (TemplateVersionSection $row): void {
            $row->refuseWhenTheTemplateIsPublished();
        });

        static::deleting(function (TemplateVersionSection $row): void {
            $row->refuseWhenTheTemplateIsPublished();
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
            'is_collapsed_by_default' => 'boolean',
        ];
    }

    /**
     * Whether this row is one the given user may reach through the given
     * template.
     *
     * The rows are addressed by their own number in a URL, under the template
     * they sit on, and neither half can be trusted on its own: the account has
     * to match, and the row has to be on a version of the template the URL
     * actually names. Without the second half, somebody could put a field on
     * one of their templates while the URL said another.
     */
    public function isReachableThrough(User $user, Template $template): bool
    {
        return $this->account_id === $user->account_id
            && $this->templateVersion->template_id === $template->id;
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
     * Get the template version the section is placed in.
     *
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }

    /**
     * Get the page of that version the section appears on.
     *
     * @return BelongsTo<TemplateVersionPage, $this>
     */
    public function templateVersionPage(): BelongsTo
    {
        return $this->belongsTo(TemplateVersionPage::class);
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

    private function refuseWhenTheTemplateIsPublished(): void
    {
        $version = $this->templateVersion;

        if ($version !== null && ! $version->status->isEditable()) {
            throw new PublishedVersionIsFrozen("Version {$version->version_number} of this template has been published. Make a draft from it instead.");
        }
    }
}
