<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Exceptions\CannotPublishWhileASectionIsStillADraft;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Freeze a draft and make it the version a new record is given.
 *
 * This is the moment everything else leans on. From here the version never
 * changes again: not the assembly, not the sections it carries, not the fields
 * inside them. Any further edit starts a new draft beside it.
 *
 * Every section it carries has to be published first. Freezing a version that
 * still points at a section somebody is editing would freeze nothing, so the
 * refusal is explicit rather than quietly publishing those sections on
 * somebody's behalf.
 *
 * The same goes for its pages: a version with none renders nothing, and one
 * with several cannot leave a tab that opens on nothing. Both are refused
 * rather than quietly repaired.
 *
 * A change note is required from the second version on, because the note is
 * what somebody deciding whether to move their records reads. The first version
 * has nothing to have changed from.
 */
class PublishTemplateVersion
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersion $templateVersion,
        private ?string $changeNote = null,
    ) {}

    public function execute(): TemplateVersion
    {
        $this->sanitize();
        $this->validate();
        $this->publish();
        $this->log();

        return $this->templateVersion;
    }

    private function sanitize(): void
    {
        $this->changeNote = TextSanitizer::nullablePlainText($this->changeNote);
    }

    private function validate(): void
    {
        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->templateVersion->status !== VersionStatusEnum::Draft) {
            throw new PublishedVersionIsFrozen("Version {$this->templateVersion->version_number} is not a draft.");
        }

        if ($this->templateVersion->previous_version_id !== null && $this->changeNote === null) {
            throw new InvalidArgumentException('Publishing a new version of a template needs a note saying what changed.');
        }

        $drafts = SectionVersion::query()
            ->whereIn('id', $this->templateVersion->assembly->pluck('section_version_id'))
            ->where('status', VersionStatusEnum::Draft)
            ->count();

        if ($drafts > 0) {
            throw new CannotPublishWhileASectionIsStillADraft("This template version carries {$drafts} sections nobody has published yet.");
        }

        $this->validateThePages();
    }

    /**
     * A version with no page renders nothing, and a tab that opens on nothing
     * is a dead end for whoever clicks it. One page on its own may be empty: a
     * record whose only content is the name imposed on it is still worth
     * writing down.
     */
    private function validateThePages(): void
    {
        $pages = $this->templateVersion->pages;

        if ($pages->isEmpty()) {
            throw new CannotPublishAnEmptyPage('This template version has no page to render.');
        }

        if ($pages->count() === 1) {
            return;
        }

        $filled = $this->templateVersion->assembly->pluck('template_version_page_id');

        $empty = $pages->reject(fn (TemplateVersionPage $page): bool => $filled->contains($page->id));

        if ($empty->isNotEmpty()) {
            throw new CannotPublishAnEmptyPage("The page {$empty->first()->label} has nothing on it.");
        }
    }

    private function publish(): void
    {
        DB::transaction(function (): void {
            $this->templateVersion->status = VersionStatusEnum::Published;
            $this->templateVersion->change_note = $this->changeNote;
            $this->templateVersion->published_at = now();
            $this->templateVersion->save();

            $template = $this->templateVersion->template;
            $template->published_version_id = $this->templateVersion->id;
            $template->save();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateVersionPublished,
            parameters: [
                'name' => $this->templateVersion->template->name,
                'version' => $this->templateVersion->version_number,
            ],
            loggable: $this->templateVersion->template,
        )->onQueue('low');
    }
}
