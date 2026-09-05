<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a page left or right, which is to say move its tab.
 */
class UpdateTemplateVersionPagePosition
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionPage $page,
        private readonly int $position,
    ) {}

    public function execute(): TemplateVersionPage
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->page;
    }

    private function validate(): void
    {
        if ($this->page->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This page is in another account.');
        }
    }

    private function update(): void
    {
        $pages = TemplateVersionPage::query()
            ->where('template_version_id', $this->page->template_version_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (TemplateVersionPage $page): bool => $page->is($this->page))
            ->values();

        $pages->splice(max(0, $this->position - 1), 0, [$this->page]);

        DB::transaction(function () use ($pages): void {
            $pages->each(function (TemplateVersionPage $page, int $index): void {
                $page->position = $index + 1;
                $page->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplatePagePositionUpdate,
            parameters: [
                'label' => $this->page->label,
                'name' => $this->page->templateVersion->template->name,
            ],
            loggable: $this->page->templateVersion->template,
        )->onQueue('low');
    }
}
