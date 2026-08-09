<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take a section off a template version.
 *
 * Only off a draft: the model refuses it on anything published, since a version
 * losing a section is exactly the kind of change publication rules out.
 *
 * The section itself is untouched. It is not owned by this template, and every
 * other version carrying it goes on as before.
 */
class RemoveSectionFromTemplateVersion
{
    private Template $template;

    private string $sectionName;

    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionSection $row,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->remove();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    private function remove(): void
    {
        $this->template = $this->row->templateVersion->template;
        $this->sectionName = $this->row->sectionVersion->section->name;

        DB::transaction(function (): void {
            $this->row->delete();

            SectionVersion::query()
                ->whereKey($this->row->section_version_id)
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionRemovedFromTemplate,
            parameters: [
                'section' => $this->sectionName,
                'name' => $this->template->name,
            ],
            loggable: $this->template,
        )->onQueue('low');
    }
}
