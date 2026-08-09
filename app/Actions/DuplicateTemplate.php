<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Make a copy of a template to change without touching the original.
 *
 * This is what somebody does with a template the product supplied: they cannot
 * edit it in place and would not want to, so they take a copy and make it
 * theirs. The copy is an ordinary template of their own from the first moment,
 * with its own slug, its own versions and nothing marking it as supplied.
 *
 * What is copied is one version, and it arrives as a draft. Nothing is
 * published on somebody's behalf: the copy has to be looked at and published
 * deliberately, which is also the moment to notice what needs changing.
 *
 * The sections themselves are not copied. A section version is frozen and can sit
 * in any number of template versions, so the copy carries the same rows the original does,
 * and changing one of them means drafting a new version of that section.
 */
class DuplicateTemplate
{
    private Template $copy;

    private TemplateVersion $source;

    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private ?string $name = null,
        private ?string $slug = null,
    ) {}

    public function execute(): Template
    {
        $this->sanitize();
        $this->validate();
        $this->duplicate();
        $this->log();

        return $this->copy;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::nullablePlainText($this->name);
        $this->slug = TextSanitizer::nullablePlainText($this->slug);
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        $source = $this->template->publishedVersion ?? $this->template->versions()->first();

        if ($source === null) {
            throw new ModelNotFoundException('This template has no version to copy.');
        }

        $this->source = $source;
    }

    private function duplicate(): void
    {
        $name = $this->name ?? __('Copy of :name', ['name' => $this->template->name]);

        $positions = Template::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        DB::transaction(function () use ($name, $positions): void {
            $this->copy = Template::query()->create([
                'account_id' => $this->template->account_id,
                'applies_to' => $this->template->applies_to,
                'entity_type_id' => $this->template->entity_type_id,
                'name' => $name,
                'name_translation_key' => null,
                'description' => $this->template->description,
                'slug' => $this->slug($name),
                'is_system' => false,
                'is_active' => true,
                'published_version_id' => null,
                'locale' => $this->template->locale,
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $version = TemplateVersion::query()->create([
                'account_id' => $this->copy->account_id,
                'template_id' => $this->copy->id,
                'version_number' => 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => null,
                'record_count' => 0,
            ]);

            $this->source->assembly->each(function (TemplateVersionSection $row) use ($version): void {
                TemplateVersionSection::query()->create([
                    'account_id' => $version->account_id,
                    'template_version_id' => $version->id,
                    'section_version_id' => $row->section_version_id,
                    'position' => $row->position,
                    'title_override' => $row->title_override,
                    'is_collapsed_by_default' => $row->is_collapsed_by_default,
                ]);

                SectionVersion::query()->whereKey($row->section_version_id)->increment('usage_count');
            });
        });
    }

    private function slug(string $name): string
    {
        return SlugBuilder::build(
            wanted: $this->slug,
            derivedFrom: $name,
            maxLength: Template::SLUG_LENGTH,
            whenEmpty: 'template',
            isTaken: fn (string $slug): bool => Template::query()
                ->withTrashed()
                ->where('account_id', $this->user->account_id)
                ->where('slug', $slug)
                ->exists(),
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateDuplication,
            parameters: [
                'source' => $this->template->name,
                'name' => $this->copy->name,
            ],
            loggable: $this->copy,
        )->onQueue('low');
    }
}
