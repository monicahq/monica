<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change how a section reads on one particular template version: the heading
 * shown above it, and whether it starts folded.
 *
 * Both are display only and neither changes any identity. A section folded on
 * one template can be open on another, and the heading a template gives it does
 * not rename the section for anybody else.
 */
class UpdateTemplateVersionSection
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionSection $row,
        private ?string $titleOverride = null,
        private readonly bool $isCollapsedByDefault = false,
    ) {}

    public function execute(): TemplateVersionSection
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->row;
    }

    private function sanitize(): void
    {
        $this->titleOverride = TextSanitizer::nullablePlainText($this->titleOverride);
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    private function update(): void
    {
        $this->row->title_override = $this->titleOverride;
        $this->row->is_collapsed_by_default = $this->isCollapsedByDefault;
        $this->row->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateSectionUpdate,
            parameters: [
                'section' => $this->row->sectionVersion->section->name,
                'name' => $this->row->templateVersion->template->name,
            ],
            loggable: $this->row->templateVersion->template,
        )->onQueue('low');
    }
}
