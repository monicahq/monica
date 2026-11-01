<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a template again when a new record is created.
 */
class ActivateTemplate
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    public function execute(): Template
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->template;
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }
    }

    private function activate(): void
    {
        $this->template->is_active = true;
        $this->template->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateActivated,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        );
    }
}
