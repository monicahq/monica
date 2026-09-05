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
