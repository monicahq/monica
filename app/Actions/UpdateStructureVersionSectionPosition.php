<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a section up or down the page it sits on.
 */
class UpdateStructureVersionSectionPosition
{
    public function __construct(
        private readonly User $user,
        private readonly StructureVersionSection $row,
        private readonly int $position,
    ) {}

    public function execute(): StructureVersionSection
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->row;
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    private function update(): void
    {
        $rows = StructureVersionSection::query()
            ->where('structure_version_page_id', $this->row->structure_version_page_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (StructureVersionSection $row): bool => $row->is($this->row))
            ->values();

        $rows->splice(max(0, $this->position - 1), 0, [$this->row]);

        DB::transaction(function () use ($rows): void {
            $rows->each(function (StructureVersionSection $row, int $index): void {
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
            action: UserActionEnum::TemplateSectionPositionUpdate,
            parameters: [
                'section' => $this->row->sectionVersion->section->name,
                'name' => $this->row->structureVersion->structure->owner->name,
            ],
            loggable: $this->row->structureVersion->structure->owner,
        );
    }
}
