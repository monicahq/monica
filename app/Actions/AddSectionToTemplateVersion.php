<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\SectionIsAlreadyInTheTemplateVersion;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Put a section on a page of a template version, at the end of that page.
 * Somebody who does not say which page means the first one, which is the whole
 * template until a second page exists.
 *
 * What is placed is an exact version of the section, never the section itself.
 * That one foreign key is what makes freezing a template version mean anything:
 * a frozen version holds frozen section versions, which hold frozen fields.
 *
 * The same section version cannot go on one template version twice. Two copies
 * would give two fields the same identity, and a value written into either
 * would have nowhere to go.
 *
 * The title override is display only. It exists so a shared section can be
 * reused under a heading that suits this template, without anybody having to
 * duplicate the section just to rename it.
 */
class AddSectionToTemplateVersion
{
    private TemplateVersionSection $row;

    private TemplateVersionPage $page;

    public function __construct(
        private readonly User $user,
        private readonly TemplateVersion $templateVersion,
        private readonly SectionVersion $sectionVersion,
        private ?string $titleOverride = null,
        private readonly bool $isCollapsedByDefault = false,
        private readonly ?TemplateVersionPage $templateVersionPage = null,
    ) {}

    public function execute(): TemplateVersionSection
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
        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }

        if ($this->sectionVersion->status === VersionStatusEnum::Retired) {
            throw new InvalidArgumentException('This version of the section is no longer offered.');
        }

        $alreadyThere = TemplateVersionSection::query()
            ->where('template_version_id', $this->templateVersion->id)
            ->where('section_version_id', $this->sectionVersion->id)
            ->exists();

        if ($alreadyThere) {
            throw new SectionIsAlreadyInTheTemplateVersion('This section is already on this template version.');
        }

        $this->page = $this->page();
    }

    /**
     * Settle which page the section lands on. Somebody who does not say lands
     * on the first one, which is the whole template for as long as nobody has
     * made a second page.
     */
    private function page(): TemplateVersionPage
    {
        if ($this->templateVersionPage === null) {
            return $this->templateVersion->pages()->firstOrFail();
        }

        if ($this->templateVersionPage->template_version_id !== $this->templateVersion->id) {
            throw new ModelNotFoundException('This page is on another template version.');
        }

        return $this->templateVersionPage;
    }

    private function add(): void
    {
        $positions = TemplateVersionSection::query()
            ->where('template_version_page_id', $this->page->id)
            ->pluck('position');

        DB::transaction(function () use ($positions): void {
            $this->row = TemplateVersionSection::query()->create([
                'account_id' => $this->templateVersion->account_id,
                'template_version_id' => $this->templateVersion->id,
                'template_version_page_id' => $this->page->id,
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
                'name' => $this->templateVersion->template->name,
            ],
            loggable: $this->templateVersion->template,
        )->onQueue('low');
    }
}
