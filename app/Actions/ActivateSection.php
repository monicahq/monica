<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a section again when somebody assembles a template.
 */
class ActivateSection
{
    public function __construct(
        private readonly User $user,
        private readonly Section $section,
    ) {}

    public function execute(): Section
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->section;
    }

    private function validate(): void
    {
        if ($this->section->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section is in another account.');
        }
    }

    private function activate(): void
    {
        $this->section->is_active = true;
        $this->section->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionActivated,
            parameters: ['name' => $this->section->name],
            loggable: $this->section,
        )->onQueue('low');
    }
}
