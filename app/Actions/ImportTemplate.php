<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\SlugBuilder;
use App\Helpers\StructureFileReader;
use App\Helpers\StructureWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Read a template out of a file and build it here.
 */
class ImportTemplate
{
    private Template $template;

    /** @var array<string, mixed> */
    private array $file;

    private StructureWriter $writer;

    public function __construct(
        private readonly User $user,
        private readonly string $raw,
    ) {}

    /** @throws StructureFileIsNotReadable */
    public function execute(): Template
    {
        $this->validate();
        $this->import();
        $this->log();

        return $this->template;
    }

    private function validate(): void
    {
        $this->file = StructureFileReader::read($this->raw, expecting: 'template');
    }

    private function import(): void
    {
        $payload = $this->file['template'];

        DB::transaction(function () use ($payload): void {
            $this->writer = new StructureWriter($this->user, $this->file);
            $this->writer->settleTheValueLists();

            $entityType = $this->writer->entityType($payload['entity_type']);
            $name = TextSanitizer::plainText($payload['name']);

            $positions = Template::query()
                ->where('account_id', $this->user->account_id)
                ->pluck('position');

            $this->template = Template::query()->create([
                'account_id' => $this->user->account_id,
                'applies_to' => TemplateAppliesToEnum::from($payload['applies_to']),
                'entity_type_id' => $entityType?->id,
                'name' => $name,
                'name_translation_key' => null,
                'description' => TextSanitizer::nullablePlainText($payload['description']),
                'slug' => $this->slug($name, $payload['slug']),
                'is_system' => false,
                'is_active' => true,
                'published_version_id' => null,
                'locale' => $payload['locale'] ?? $this->user->locale ?? config('app.locale'),
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $version = TemplateVersion::query()->create([
                'account_id' => $this->user->account_id,
                'template_id' => $this->template->id,
                'version_number' => 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => null,
                'record_count' => 0,
            ]);

            foreach ($payload['pages'] as $page) {
                $this->page($version, $page);
            }
        });
    }

    /** @param  array<string, mixed>  $payload */
    private function page(TemplateVersion $version, array $payload): void
    {
        $page = TemplateVersionPage::query()->create([
            'account_id' => $this->user->account_id,
            'template_version_id' => $version->id,
            'key' => $payload['key'],
            'label' => TextSanitizer::plainText($payload['label']),
            'label_translation_key' => null,
            'position' => $payload['position'],
        ]);

        foreach ($payload['sections'] as $row) {
            $sectionVersion = $this->writer->section($row['section']);

            TemplateVersionSection::query()->create([
                'account_id' => $this->user->account_id,
                'template_version_id' => $version->id,
                'template_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $row['position'],
                'title_override' => TextSanitizer::nullablePlainText($row['title_override']),
                'is_collapsed_by_default' => $row['is_collapsed_by_default'],
            ]);

            $sectionVersion->increment('usage_count');
        }
    }

    private function slug(string $name, ?string $wanted): string
    {
        return SlugBuilder::build(
            wanted: $wanted,
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
            action: UserActionEnum::TemplateImported,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
