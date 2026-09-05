<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a template, say what it is for, or change the language it is written in.
 */
class UpdateTemplate
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private string $name,
        private ?string $description = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): Template
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->template;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }
    }

    private function update(): void
    {
        $this->template->name = $this->name;
        $this->template->description = $this->description;
        $this->template->locale = $this->locale ?? $this->template->locale;
        $this->template->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateUpdate,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
