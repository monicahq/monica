<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Start a new draft of a template, copied from a version that already exists.
 *
 * A template has at most one draft at a time, which is what the builder and the
 * templates screen both assume when they reach for it with a single lookup. So a
 * call made while one is already open answers with that draft rather than
 * starting a second nobody could reach.
 */
class CreateStructureVersionDraft
{
    private StructureVersion $draft;

    public function __construct(
        private readonly User $user,
        private readonly StructureVersion $structureVersion,
    ) {}

    public function execute(): StructureVersion
    {
        $this->validate();

        $open = $this->openDraft();

        if ($open !== null) {
            return $open;
        }

        $this->create();
        $this->log();

        return $this->draft;
    }

    private function openDraft(): ?StructureVersion
    {
        return StructureVersion::query()
            ->where('structure_id', $this->structureVersion->structure_id)
            ->where('status', VersionStatusEnum::Draft)
            ->orderBy('version_number')
            ->first();
    }

    private function validate(): void
    {
        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }
    }

    private function create(): void
    {
        $highest = (int) StructureVersion::query()
            ->where('structure_id', $this->structureVersion->structure_id)
            ->max('version_number');

        DB::transaction(function () use ($highest): void {
            $this->draft = StructureVersion::query()->create([
                'account_id' => $this->structureVersion->account_id,
                'structure_id' => $this->structureVersion->structure_id,
                'version_number' => $highest + 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => $this->structureVersion->id,
                'record_count' => 0,
            ]);

            $pages = $this->copyThePages();

            $this->structureVersion->assembly->each(function (StructureVersionSection $row) use ($pages): void {
                StructureVersionSection::query()->create([
                    'account_id' => $row->account_id,
                    'structure_version_id' => $this->draft->id,
                    'structure_version_page_id' => $pages[$row->structure_version_page_id],
                    'section_version_id' => $row->section_version_id,
                    'position' => $row->position,
                    'title_override' => $row->title_override,
                    'shows_title' => $row->shows_title,
                    'is_collapsed_by_default' => $row->is_collapsed_by_default,
                ]);

                SectionVersion::query()->whereKey($row->section_version_id)->increment('usage_count');
            });
        });
    }

    /** @return array<int, int> */
    private function copyThePages(): array
    {
        $pages = [];

        $this->structureVersion->pages->each(function (StructureVersionPage $page) use (&$pages): void {
            $copy = StructureVersionPage::query()->create([
                'account_id' => $page->account_id,
                'structure_version_id' => $this->draft->id,
                'key' => $page->key,
                'label' => $page->getRawOriginal('label'),
                'label_translation_key' => $page->label_translation_key,
                'position' => $page->position,
            ]);

            $pages[$page->id] = $copy->id;
        });

        return $pages;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateVersionDrafted,
            parameters: [
                'name' => $this->draft->structure->owner->name,
                'version' => $this->draft->version_number,
            ],
            loggable: $this->draft->structure->owner,
        )->onQueue('low');
    }
}
