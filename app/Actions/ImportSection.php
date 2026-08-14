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
use App\Models\Template;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Read one section out of a file and put it on a page of a template.
 *
 * A section is the useful unit far more often than a whole template is: the
 * medical follow up block somebody worked out drops straight into what somebody
 * else has already built, while the rest of their template is nobody's
 * business. This is the other end of that, and it is why the import is reached
 * from the page it lands on rather than from a screen that has to ask.
 *
 * It lands at the end of the open page of a draft. A published version is
 * frozen by definition, and a section arriving into one would change what
 * records already render, which is exactly what publication rules out.
 *
 * As with a whole template, the file is read here from the raw text rather than
 * from anything the checking screen concluded about it.
 */
class ImportSection
{
    private TemplateVersionSection $row;

    /**
     * @var array<string, mixed>
     */
    private array $file;

    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly TemplateVersionPage $page,
        private readonly string $raw,
    ) {}

    /**
     * @throws StructureFileIsNotReadable
     */
    public function execute(): TemplateVersionSection
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

        if ($this->page->account_id !== $this->user->account_id || $this->page->templateVersion->template_id !== $this->template->id) {
            throw new ModelNotFoundException('This page is not on this template.');
        }

        if (! $this->page->templateVersion->status->isEditable()) {
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

            $positions = TemplateVersionSection::query()
                ->where('template_version_page_id', $this->page->id)
                ->pluck('position');

            $this->row = TemplateVersionSection::query()->create([
                'account_id' => $this->user->account_id,
                'template_version_id' => $this->page->template_version_id,
                'template_version_page_id' => $this->page->id,
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
        )->onQueue('low');
    }
}
