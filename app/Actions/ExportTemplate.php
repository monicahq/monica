<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ListingHasNothingToPublish;
use App\Helpers\StructureFile;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Write a template out as a file somebody keeps.
 */
class ExportTemplate
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    /** @return array<string, mixed> */
    public function execute(): array
    {
        $this->validate();
        $file = StructureFile::ofTemplate($this->template);
        $this->log();

        return $file;
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        if ($this->template->structure->published_version_id === null) {
            throw new ListingHasNothingToPublish("{$this->template->name} has never been published, so there is nothing frozen to write down.");
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateExported,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
