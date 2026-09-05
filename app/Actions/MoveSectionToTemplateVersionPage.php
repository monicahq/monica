<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a section from the page it sits on to another page of the same template version.
 */
class MoveSectionToTemplateVersionPage
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionSection $row,
        private readonly TemplateVersionPage $page,
    ) {}

    public function execute(): TemplateVersionSection
    {
        $this->validate();
        $this->move();
        $this->log();

        return $this->row;
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }

        if ($this->page->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This page is in another account.');
        }

        if ($this->page->template_version_id !== $this->row->template_version_id) {
            throw new ModelNotFoundException('This page is on another template version.');
        }
    }

    private function move(): void
    {
        $left = $this->row->template_version_page_id;

        if ($left === $this->page->id) {
            return;
        }

        $positions = TemplateVersionSection::query()
            ->where('template_version_page_id', $this->page->id)
            ->pluck('position');

        DB::transaction(function () use ($left, $positions): void {
            $this->row->template_version_page_id = $this->page->id;
            $this->row->position = ($positions->max() ?? 0) + 1;
            $this->row->save();

            TemplateVersionSection::query()
                ->where('template_version_page_id', $left)
                ->orderBy('position')
                ->get()
                ->each(function (TemplateVersionSection $row, int $index): void {
                    $row->position = $index + 1;
                    $row->save();
                });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionMovedToPage,
            parameters: [
                'section' => $this->row->sectionVersion->section->name,
                'label' => $this->page->label,
            ],
            loggable: $this->row->templateVersion->template,
        )->onQueue('low');
    }
}
