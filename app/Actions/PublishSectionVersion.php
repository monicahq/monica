<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Helpers\TextSanitizer;
use App\Helpers\ValueListSnapshot;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Freeze a draft of a section, so structure versions can be built on something that will not move
 * under them.
 */
class PublishSectionVersion
{
    public function __construct(
        private readonly User $user,
        private readonly SectionVersion $sectionVersion,
        private ?string $changeNote = null,
    ) {}

    public function execute(): SectionVersion
    {
        $this->sanitize();
        $this->validate();
        $this->publish();
        $this->log();

        return $this->sectionVersion;
    }

    private function sanitize(): void
    {
        $this->changeNote = TextSanitizer::nullablePlainText($this->changeNote);
    }

    private function validate(): void
    {
        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }

        if ($this->sectionVersion->status !== VersionStatusEnum::Draft) {
            throw new PublishedVersionIsFrozen("Version {$this->sectionVersion->version_number} is not a draft.");
        }

        if ($this->sectionVersion->previous_version_id !== null && $this->changeNote === null) {
            throw new InvalidArgumentException('Publishing a new version of a section needs a note saying what changed.');
        }
    }

    private function publish(): void
    {
        DB::transaction(function (): void {
            $this->sectionVersion->status = VersionStatusEnum::Published;
            $this->sectionVersion->change_note = $this->changeNote;
            $this->sectionVersion->resolved_value_lists = ValueListSnapshot::of($this->sectionVersion);
            $this->sectionVersion->published_at = now();
            $this->sectionVersion->save();

            $section = $this->sectionVersion->section;
            $section->published_version_id = $this->sectionVersion->id;
            $section->save();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionVersionPublished,
            parameters: [
                'name' => $this->sectionVersion->section->name,
                'version' => $this->sectionVersion->version_number,
            ],
            loggable: $this->sectionVersion->section,
        );
    }
}
