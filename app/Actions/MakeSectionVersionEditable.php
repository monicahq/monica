<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SectionVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Get hold of a version of a section that can be written to, for somebody who
 * has just started editing a field on one particular template.
 *
 * A section is not owned by the template carrying it. It may have been
 * published, and it may be sitting on somebody else's template at the same
 * time, so the version on the page is very often one that must not be touched.
 * Refusing at that point would mean asking the user to understand versioning
 * before they can rename a label, so the draft is made for them instead: the
 * section gets a new version, the fields are copied into it, and this template
 * starts pointing at the copy.
 *
 * Nothing else moves. The published version stays exactly as it was, every
 * other template carrying it renders what it always did, and the records on
 * those templates never notice. What changes is only which version this draft
 * template points at, and a draft template has promised nobody anything.
 *
 * The version is handed back untouched when it is already a draft nothing else
 * carries, which is the ordinary case of somebody building a section they have
 * just added.
 */
class MakeSectionVersionEditable
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionSection $row,
    ) {}

    public function execute(): SectionVersion
    {
        $this->validate();

        if ($this->isAlreadyEditable()) {
            return $this->row->sectionVersion;
        }

        return $this->draft();
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    /**
     * Whether the version on the template can be written to as it stands: a
     * draft, and one no other template is carrying. A draft shared with a
     * second template is as untouchable as a published one, since editing it
     * would change what that other template asks for.
     */
    private function isAlreadyEditable(): bool
    {
        $version = $this->row->sectionVersion;

        return $version->status->isEditable() && $version->usage_count <= 1;
    }

    private function draft(): SectionVersion
    {
        $previous = $this->row->sectionVersion;

        return DB::transaction(function () use ($previous): SectionVersion {
            $draft = new CreateSectionVersionDraft(
                user: $this->user,
                sectionVersion: $previous,
            )->execute();

            $this->row->section_version_id = $draft->id;
            $this->row->save();

            SectionVersion::query()->whereKey($draft->id)->increment('usage_count');

            SectionVersion::query()
                ->whereKey($previous->id)
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');

            return $draft->refresh();
        });
    }
}
