<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a section from the page it sits on to another page of the same structure version.
 */
class MoveSectionToStructureVersionPage
{
    public function __construct(
        private readonly User $user,
        private readonly StructureVersionSection $row,
        private readonly StructureVersionPage $page,
    ) {}

    public function execute(): StructureVersionSection
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

        if ($this->page->structure_version_id !== $this->row->structure_version_id) {
            throw new ModelNotFoundException('This page is on another structure version.');
        }
    }

    private function move(): void
    {
        $left = $this->row->structure_version_page_id;

        if ($left === $this->page->id) {
            return;
        }

        $positions = StructureVersionSection::query()
            ->where('structure_version_page_id', $this->page->id)
            ->pluck('position');

        DB::transaction(function () use ($left, $positions): void {
            $this->row->structure_version_page_id = $this->page->id;
            $this->row->position = ($positions->max() ?? 0) + 1;
            $this->row->save();

            StructureVersionSection::query()
                ->where('structure_version_page_id', $left)
                ->orderBy('position')
                ->get()
                ->each(function (StructureVersionSection $row, int $index): void {
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
            loggable: $this->row->structureVersion->structure->owner,
        )->onQueue('low');
    }
}
