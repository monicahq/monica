<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Throw away a draft of a section, and the fields in it.
 */
class DestroySectionVersion
{
    private Section $section;

    private int $versionNumber;

    public function __construct(
        private readonly User $user,
        private readonly SectionVersion $sectionVersion,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->section = $this->sectionVersion->section;
        $this->versionNumber = $this->sectionVersion->version_number;

        $this->sectionVersion->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionVersionDeletion,
            parameters: [
                'name' => $this->section->name,
                'version' => $this->versionNumber,
            ],
            loggable: $this->section,
        )->onQueue('low');
    }
}
