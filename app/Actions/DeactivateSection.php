<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop offering a section when somebody assembles a template.
 *
 * Every template version already carrying it is untouched and goes on rendering it. What
 * stops is the section turning up in the list of things to add.
 */
class DeactivateSection
{
    public function __construct(
        private readonly User $user,
        private readonly Section $section,
    ) {}

    public function execute(): Section
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->section;
    }

    private function validate(): void
    {
        if ($this->section->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->section->is_active = false;
        $this->section->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionDeactivated,
            parameters: ['name' => $this->section->name],
            loggable: $this->section,
        )->onQueue('low');
    }
}
