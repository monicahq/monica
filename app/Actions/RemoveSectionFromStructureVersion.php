<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\ActivityType;
use App\Models\Template;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take a section off a structure version, whichever kind of owner the structure has.
 */
class RemoveSectionFromStructureVersion
{
    private Template|ActivityType $owner;

    private string $sectionName;

    public function __construct(
        private readonly User $user,
        private readonly StructureVersionSection $row,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->remove();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    private function remove(): void
    {
        $this->owner = $this->row->structureVersion->structure->owner;
        $this->sectionName = $this->row->sectionVersion->section->name;

        DB::transaction(function (): void {
            $this->row->delete();

            SectionVersion::query()
                ->whereKey($this->row->section_version_id)
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionRemovedFromTemplate,
            parameters: [
                'section' => $this->sectionName,
                'name' => $this->owner->name,
            ],
            loggable: $this->owner,
        )->onQueue('low');
    }
}
