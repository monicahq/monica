<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotDeleteASectionInUse;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a section, which is only allowed while no published template version carries one of its
 * versions.
 */
class DestroySection
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Section $section,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->section->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section is in another account.');
        }

        $versions = TemplateVersionSection::query()
            ->whereIn('section_version_id', $this->section->versions()->select('id'))
            ->whereHas('templateVersion', fn ($query) => $query->where('status', '!=', VersionStatusEnum::Draft))
            ->count();

        if ($versions > 0) {
            throw new CannotDeleteASectionInUse("This section sits in {$versions} published template versions.");
        }
    }

    private function destroy(): void
    {
        $this->name = $this->section->name;

        $this->section->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->section,
        )->onQueue('low');
    }
}
