<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a page left or right, which is to say move its tab.
 */
class UpdateStructureVersionPagePosition
{
    public function __construct(
        private readonly User $user,
        private readonly StructureVersionPage $page,
        private readonly int $position,
    ) {}

    public function execute(): StructureVersionPage
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
        $pages = StructureVersionPage::query()
            ->where('structure_version_id', $this->page->structure_version_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (StructureVersionPage $page): bool => $page->is($this->page))
            ->values();

        $pages->splice(max(0, $this->position - 1), 0, [$this->page]);

        DB::transaction(function () use ($pages): void {
            $pages->each(function (StructureVersionPage $page, int $index): void {
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
                'name' => $this->page->structureVersion->structure->owner->name,
            ],
            loggable: $this->page->structureVersion->structure->owner,
        );
    }
}
