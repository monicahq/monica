<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Start a new draft of a template, copied from a version that already exists.
 *
 * This is what editing a published version means. The published one is left
 * exactly as it is, records keep rendering with it, and the changes happen
 * beside it on something nobody is using yet. Publishing the draft is a
 * separate act afterwards.
 *
 * The assembly is copied row for row, pointing at the same section versions
 * rather than at copies of them. A section version is frozen and shareable, so
 * two versions carrying the same section carry the same rows, and the count of
 * template versions containing it goes up.
 */
class CreateTemplateVersionDraft
{
    private TemplateVersion $draft;

    public function __construct(
        private readonly User $user,
        private readonly TemplateVersion $templateVersion,
    ) {}

    public function execute(): TemplateVersion
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->draft;
    }

    private function validate(): void
    {
        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }
    }

    private function create(): void
    {
        $highest = (int) TemplateVersion::query()
            ->where('template_id', $this->templateVersion->template_id)
            ->max('version_number');

        DB::transaction(function () use ($highest): void {
            $this->draft = TemplateVersion::query()->create([
                'account_id' => $this->templateVersion->account_id,
                'template_id' => $this->templateVersion->template_id,
                'version_number' => $highest + 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => $this->templateVersion->id,
                'record_count' => 0,
            ]);

            $this->templateVersion->assembly->each(function (TemplateVersionSection $row): void {
                TemplateVersionSection::query()->create([
                    'account_id' => $row->account_id,
                    'template_version_id' => $this->draft->id,
                    'section_version_id' => $row->section_version_id,
                    'position' => $row->position,
                    'title_override' => $row->title_override,
                    'is_collapsed_by_default' => $row->is_collapsed_by_default,
                ]);

                SectionVersion::query()->whereKey($row->section_version_id)->increment('usage_count');
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateVersionDrafted,
            parameters: [
                'name' => $this->draft->template->name,
                'version' => $this->draft->version_number,
            ],
            loggable: $this->draft->template,
        )->onQueue('low');
    }
}
