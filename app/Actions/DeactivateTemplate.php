<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop a template being offered when a new record is created.
 *
 * This is the answer to a template somebody has finished with, and it is a
 * different thing from deleting it. Every record already on it stays readable
 * and editable, rendering exactly the version it was made with.
 */
class DeactivateTemplate
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    public function execute(): Template
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->template;
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->template->is_active = false;
        $this->template->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateDeactivated,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
