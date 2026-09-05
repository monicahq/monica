<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stop offering a version of a section when somebody assembles a template version.
 */
class RetireSectionVersion
{
    public function __construct(
        private readonly User $user,
        private readonly SectionVersion $sectionVersion,
    ) {}

    public function execute(): SectionVersion
    {
        $this->validate();
        $this->retire();
        $this->log();

        return $this->sectionVersion;
    }

    private function validate(): void
    {
        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }

        if ($this->sectionVersion->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('Only a published version can be retired. A draft is thrown away instead.');
        }
    }

    private function retire(): void
    {
        DB::transaction(function (): void {
            $this->sectionVersion->status = VersionStatusEnum::Retired;
            $this->sectionVersion->retired_at = now();
            $this->sectionVersion->save();

            $section = $this->sectionVersion->section;

            if ($section->published_version_id === $this->sectionVersion->id) {
                $section->published_version_id = null;
                $section->save();
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionVersionRetired,
            parameters: [
                'name' => $this->sectionVersion->section->name,
                'version' => $this->sectionVersion->version_number,
            ],
            loggable: $this->sectionVersion->section,
        )->onQueue('low');
    }
}
