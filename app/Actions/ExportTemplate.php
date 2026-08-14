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
 *
 * This is the way out that involves nobody else. Nothing is sent anywhere and
 * nothing is offered to anybody: the structure is written down, handed over,
 * and what happens to it after that is not our business. It is how a template
 * moves between two vaults of the same person, how it reaches one friend
 * without being published to everybody, and how somebody keeps a copy of a
 * shape that took an afternoon to get right.
 *
 * What leaves is the published version and only the published version. A draft
 * is something still being argued with, and a file describing it would describe
 * something that no longer exists by the time anybody reads it.
 *
 * The export is logged, because bytes leaving an account is exactly the kind of
 * thing somebody should be able to look back at.
 */
class ExportTemplate
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    /**
     * @return array<string, mixed>
     */
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

        if ($this->template->published_version_id === null) {
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
