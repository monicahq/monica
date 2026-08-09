<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteATemplateInUse;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a template, which is only allowed while no record points at any of its
 * versions.
 *
 * A template that still has records refuses to go. Those records would be left
 * with nothing to render with, and taking two hundred contacts down with the
 * word "Contact" is not something a single button should be able to do. The
 * answer is to deactivate it: it stops being offered to anything new and every
 * record already on it is untouched.
 *
 * The deletion is soft, and the slug is not released with it, so a link that
 * went out pointing at this template cannot later land on a different one. The
 * versions stay where they are, which is what makes bringing it back possible.
 */
class DestroyTemplate
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        $records = (int) $this->template->versions()->sum('record_count');

        if ($records > 0) {
            throw new CannotDeleteATemplateInUse("This template still renders {$records} records.");
        }
    }

    private function destroy(): void
    {
        $this->name = $this->template->name;

        $this->template->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
