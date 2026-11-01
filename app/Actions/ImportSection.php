<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Helpers\StructureWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Read one section out of a file and put it on a page of a template.
 */
class ImportSection
{
    private StructureVersionSection $row;

    /** @var array<string, mixed> */
    private array $file;

    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly StructureVersionPage $page,
        private readonly string $raw,
    ) {}

    /** @throws StructureFileIsNotReadable */
    public function execute(): StructureVersionSection
    {
        $this->validate();
        $this->import();
        $this->log();

        return $this->row;
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        if ($this->page->account_id !== $this->user->account_id || $this->page->structureVersion->structure_id !== $this->template->structure->id) {
            throw new ModelNotFoundException('This page is not on this template.');
        }

        if (! $this->page->structureVersion->status->isEditable()) {
            throw new PublishedVersionIsFrozen('This version of the template is published, so nothing can be added to it.');
        }

        $this->file = StructureFileReader::read($this->raw, expecting: 'section');
    }

    private function import(): void
    {
        DB::transaction(function (): void {
            $writer = new StructureWriter($this->user, $this->file);
            $writer->settleTheValueLists();

            $sectionVersion = $writer->section($this->file['section']);

            $positions = StructureVersionSection::query()
                ->where('structure_version_page_id', $this->page->id)
                ->pluck('position');

            $this->row = StructureVersionSection::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $this->page->structure_version_id,
                'structure_version_page_id' => $this->page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => ($positions->max() ?? 0) + 1,
                'title_override' => null,
                'is_collapsed_by_default' => false,
            ]);

            $sectionVersion->increment('usage_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionImported,
            parameters: [
                'section' => TextSanitizer::plainText($this->file['section']['name']),
                'name' => $this->template->name,
            ],
            loggable: $this->template,
        );
    }
}
