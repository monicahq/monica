<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ListingHasNothingToPublish;
use App\Helpers\StructureFile;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Write one section of a template out as a file, on its own.
 */
class ExportSection
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly TemplateVersionSection $row,
    ) {}

    /** @return array<string, mixed> */
    public function execute(): array
    {
        $this->validate();
        $file = StructureFile::ofSection($this->row);
        $this->log();

        return $file;
    }

    private function validate(): void
    {
        if (! $this->row->isReachableThrough($this->user, $this->template)) {
            throw new ModelNotFoundException('This section is not on this template.');
        }

        if ($this->row->sectionVersion->status->isEditable()) {
            throw new ListingHasNothingToPublish("{$this->row->sectionVersion->section->name} is still a draft, so there is nothing frozen to write down.");
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionExported,
            parameters: [
                'section' => $this->row->sectionVersion->section->name,
                'name' => $this->template->name,
            ],
            loggable: $this->template,
        )->onQueue('low');
    }
}
