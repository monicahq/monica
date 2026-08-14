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
 *
 * A section is the useful unit far more often than a whole template is. The
 * rest of somebody's template is usually nobody else's business, while the
 * medical follow up block they worked out drops straight into whatever
 * somebody else has already built. That is the reason a section is its own
 * object in the first place, and this is where that reason pays.
 *
 * The section is named through the row that places it, rather than on its own,
 * so what is written down is the version this template pins. That version can
 * be older than what the section has published since, and what the file says
 * has to be what the screen it was exported from says.
 */
class ExportSection
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly TemplateVersionSection $row,
    ) {}

    /**
     * @return array<string, mixed>
     */
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
