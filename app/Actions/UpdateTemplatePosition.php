<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a template in the picker, so the ones reached for daily come first.
 */
class UpdateTemplatePosition
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly int $position,
    ) {}

    public function execute(): Template
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->template;
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }
    }

    private function update(): void
    {
        $templates = Template::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (Template $template): bool => $template->is($this->template))
            ->values();

        $templates->splice(max(0, $this->position - 1), 0, [$this->template]);

        DB::transaction(function () use ($templates): void {
            $templates->each(function (Template $template, int $index): void {
                $template->position = $index + 1;
                $template->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplatePositionUpdate,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
