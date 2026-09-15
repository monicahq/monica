<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\ActivityType;
use App\Models\Template;
use App\Models\StructureVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Throw away a draft of a template.
 */
class DestroyStructureVersion
{
    private Template|ActivityType $owner;

    private int $versionNumber;

    public function __construct(
        private readonly User $user,
        private readonly StructureVersion $structureVersion,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->owner = $this->structureVersion->structure->owner;
        $this->versionNumber = $this->structureVersion->version_number;

        $sectionVersionIds = $this->structureVersion->assembly->pluck('section_version_id');

        DB::transaction(function () use ($sectionVersionIds): void {
            $this->structureVersion->delete();

            SectionVersion::query()
                ->whereIn('id', $sectionVersionIds)
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateVersionDeletion,
            parameters: [
                'name' => $this->owner->name,
                'version' => $this->versionNumber,
            ],
            loggable: $this->owner,
        )->onQueue('low');
    }
}
