<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\SectionIsAlreadyInTheStructureVersion;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Put a section on a page of a structure version, at the end of that page.
 */
class AddSectionToStructureVersion
{
    private StructureVersionSection $row;

    private StructureVersionPage $page;

    public function __construct(
        private readonly User $user,
        private readonly StructureVersion $structureVersion,
        private readonly SectionVersion $sectionVersion,
        private ?string $titleOverride = null,
        private readonly bool $isCollapsedByDefault = false,
        private readonly ?StructureVersionPage $structureVersionPage = null,
    ) {}

    public function execute(): StructureVersionSection
    {
        $this->sanitize();
        $this->validate();
        $this->add();
        $this->log();

        return $this->row;
    }

    private function sanitize(): void
    {
        $this->titleOverride = TextSanitizer::nullablePlainText($this->titleOverride);
    }

    private function validate(): void
    {
        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }

        if ($this->sectionVersion->status === VersionStatusEnum::Retired) {
            throw new InvalidArgumentException('This version of the section is no longer offered.');
        }

        $alreadyThere = StructureVersionSection::query()
            ->where('structure_version_id', $this->structureVersion->id)
            ->where('section_version_id', $this->sectionVersion->id)
            ->exists();

        if ($alreadyThere) {
            throw new SectionIsAlreadyInTheStructureVersion('This section is already on this structure version.');
        }

        $this->page = $this->page();
    }

    private function page(): StructureVersionPage
    {
        if ($this->structureVersionPage === null) {
            return $this->structureVersion->pages()->firstOrFail();
        }

        if ($this->structureVersionPage->structure_version_id !== $this->structureVersion->id) {
            throw new ModelNotFoundException('This page is on another structure version.');
        }

        return $this->structureVersionPage;
    }

    private function add(): void
    {
        $positions = StructureVersionSection::query()
            ->where('structure_version_page_id', $this->page->id)
            ->pluck('position');

        DB::transaction(function () use ($positions): void {
            $this->row = StructureVersionSection::query()->create([
                'account_id' => $this->structureVersion->account_id,
                'structure_version_id' => $this->structureVersion->id,
                'structure_version_page_id' => $this->page->id,
                'section_version_id' => $this->sectionVersion->id,
                'position' => ($positions->max() ?? 0) + 1,
                'title_override' => $this->titleOverride,
                'is_collapsed_by_default' => $this->isCollapsedByDefault,
            ]);

            $this->sectionVersion->increment('usage_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionAddedToTemplate,
            parameters: [
                'section' => $this->sectionVersion->section->name,
                'name' => $this->structureVersion->structure->owner->name,
            ],
            loggable: $this->structureVersion->structure->owner,
        )->onQueue('low');
    }
}
