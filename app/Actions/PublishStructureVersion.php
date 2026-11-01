<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Freeze a draft and make it the version a new record is given.
 *
 * A structure version cannot be published while a section it carries is still a
 * draft, so publishing one publishes those sections first. That belongs here
 * rather than in whatever asked for the publication: the rule is about freezing,
 * and every caller has to obey it identically or the freeze means nothing.
 *
 * The structure belongs to a template or to a life event type, and the journal
 * names whichever it is, since neither word means anything on the other's screen.
 */
class PublishStructureVersion
{
    public function __construct(
        private readonly User $user,
        private readonly StructureVersion $structureVersion,
        private ?string $changeNote = null,
    ) {}

    public function execute(): StructureVersion
    {
        $this->sanitize();
        $this->validateTheVersion();
        $this->publishTheSectionsItCarries();
        $this->validateThePages();
        $this->publish();
        $this->log();

        return $this->structureVersion;
    }

    private function publishTheSectionsItCarries(): void
    {
        foreach ($this->structureVersion->assembly as $row) {
            $sectionVersion = $row->sectionVersion;

            if (! $sectionVersion->status->isEditable()) {
                continue;
            }

            new PublishSectionVersion(
                user: $this->user,
                sectionVersion: $sectionVersion,
                changeNote: $sectionVersion->previous_version_id === null
                    ? null
                    : $this->sectionChangeNote(),
            )->execute();
        }
    }

    private function sectionChangeNote(): string
    {
        if ($this->structureVersion->structure->owner instanceof LifeEventType) {
            return __('Published with the life event type.');
        }

        return __('Published with the template.');
    }

    private function sanitize(): void
    {
        $this->changeNote = TextSanitizer::nullablePlainText($this->changeNote);
    }

    /**
     * What has to hold before anything is written. It runs before the sections are
     * published, so a caller who may not touch this version cannot publish the
     * sections it carries on the way to being refused.
     */
    private function validateTheVersion(): void
    {
        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($this->structureVersion->status !== VersionStatusEnum::Draft) {
            throw new PublishedVersionIsFrozen("Version {$this->structureVersion->version_number} is not a draft.");
        }

        if ($this->structureVersion->previous_version_id !== null && $this->changeNote === null) {
            throw new InvalidArgumentException('Publishing a new version of a structure needs a note saying what changed.');
        }
    }

    private function validateThePages(): void
    {
        $pages = $this->structureVersion->pages;

        if ($pages->isEmpty()) {
            throw new CannotPublishAnEmptyPage('This structure version has no page to render.');
        }

        if ($pages->count() === 1) {
            return;
        }

        $filled = $this->structureVersion->assembly->pluck('structure_version_page_id');

        $empty = $pages->reject(fn (StructureVersionPage $page): bool => $filled->contains($page->id));

        if ($empty->isNotEmpty()) {
            throw new CannotPublishAnEmptyPage("The page {$empty->first()->label} has nothing on it.");
        }
    }

    private function publish(): void
    {
        DB::transaction(function (): void {
            $this->structureVersion->status = VersionStatusEnum::Published;
            $this->structureVersion->change_note = $this->changeNote;
            $this->structureVersion->published_at = now();
            $this->structureVersion->save();

            $structure = $this->structureVersion->structure;
            $structure->published_version_id = $this->structureVersion->id;
            $structure->save();
        });
    }

    private function log(): void
    {
        $owner = $this->structureVersion->structure->owner;

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $owner instanceof LifeEventType
                ? UserActionEnum::LifeEventTypeFieldsPublished
                : UserActionEnum::TemplateVersionPublished,
            parameters: [
                'name' => $owner->name,
                'version' => $this->structureVersion->version_number,
            ],
            loggable: $owner,
        );
    }
}
