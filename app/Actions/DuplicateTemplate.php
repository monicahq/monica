<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Make a copy of a template to change without touching the original.
 */
class DuplicateTemplate
{
    private Template $copy;

    private StructureVersion $source;

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

        $source = $this->template->structure->publishedVersion ?? $this->template->versions()->first();

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
                'name' => $name,
                'name_translation_key' => null,
                'description' => $this->template->description,
                'slug' => $this->slug($name),
                'is_system' => false,
                'is_active' => true,
                'locale' => $this->template->locale,
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $structure = Structure::query()->create([
                'account_id' => $this->copy->account_id,
                'owner_type' => Template::class,
                'owner_id' => $this->copy->id,
                'applies_to' => $this->template->structure->applies_to,
                'entity_type_id' => $this->template->structure->entity_type_id,
                'published_version_id' => null,
            ]);

            $version = StructureVersion::query()->create([
                'account_id' => $this->copy->account_id,
                'structure_id' => $structure->id,
                'version_number' => 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => null,
                'record_count' => 0,
            ]);

            $pages = $this->copyThePages($version);

            $this->source->assembly->each(function (StructureVersionSection $row) use ($version, $pages): void {
                StructureVersionSection::query()->create([
                    'account_id' => $version->account_id,
                    'structure_version_id' => $version->id,
                    'structure_version_page_id' => $pages[$row->structure_version_page_id],
                    'section_version_id' => $row->section_version_id,
                    'position' => $row->position,
                    'title_override' => $row->title_override,
                    'is_collapsed_by_default' => $row->is_collapsed_by_default,
                ]);

                SectionVersion::query()->whereKey($row->section_version_id)->increment('usage_count');
            });
        });
    }

    /** @return array<int, int> */
    private function copyThePages(StructureVersion $version): array
    {
        $pages = [];

        $this->source->pages->each(function (StructureVersionPage $page) use ($version, &$pages): void {
            $copy = StructureVersionPage::query()->create([
                'account_id' => $version->account_id,
                'structure_version_id' => $version->id,
                'key' => $page->key,
                'label' => $page->getRawOriginal('label'),
                'label_translation_key' => $page->label_translation_key,
                'position' => $page->position,
            ]);

            $pages[$page->id] = $copy->id;
        });

        return $pages;
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
