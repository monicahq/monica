<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteAPageInUse;
use App\Exceptions\CannotDeleteTheLastPage;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take a page off a template version.
 */
class DestroyTemplateVersionPage
{
    private string $label;

    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionPage $page,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->page->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This page is in another account.');
        }

        $remaining = TemplateVersionPage::query()
            ->where('template_version_id', $this->page->template_version_id)
            ->count();

        if ($remaining <= 1) {
            throw new CannotDeleteTheLastPage('A template version always has at least one page.');
        }

        $sections = TemplateVersionSection::query()
            ->where('template_version_page_id', $this->page->id)
            ->count();

        if ($sections > 0) {
            throw new CannotDeleteAPageInUse("This page still holds {$sections} sections.");
        }
    }

    private function destroy(): void
    {
        $this->label = $this->page->label;

        DB::transaction(function (): void {
            $this->page->delete();

            TemplateVersionPage::query()
                ->where('template_version_id', $this->page->template_version_id)
                ->orderBy('position')
                ->get()
                ->each(function (TemplateVersionPage $page, int $index): void {
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
            action: UserActionEnum::TemplatePageDeletion,
            parameters: [
                'label' => $this->label,
                'name' => $this->page->templateVersion->template->name,
            ],
            loggable: $this->page->templateVersion->template,
        )->onQueue('low');
    }
}
