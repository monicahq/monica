<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\SlugBuilder;
use App\Helpers\StructureFileReader;
use App\Helpers\StructureWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
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
                'name' => $name,
                'name_translation_key' => null,
                'description' => TextSanitizer::nullablePlainText($payload['description']),
                'slug' => $this->slug($name, $payload['slug']),
                'is_system' => false,
                'is_active' => true,
                'locale' => $payload['locale'] ?? $this->user->locale ?? config('app.locale'),
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $structure = Structure::query()->create([
                'account_id' => $this->user->account_id,
                'owner_type' => Template::class,
                'owner_id' => $this->template->id,
                'applies_to' => StructureAppliesToEnum::from($payload['applies_to']),
                'entity_type_id' => $entityType?->id,
                'published_version_id' => null,
            ]);

            $version = StructureVersion::query()->create([
                'account_id' => $this->user->account_id,
                'structure_id' => $structure->id,
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
    private function page(StructureVersion $version, array $payload): void
    {
        $page = StructureVersionPage::query()->create([
            'account_id' => $this->user->account_id,
            'structure_version_id' => $version->id,
            'key' => $payload['key'],
            'label' => TextSanitizer::plainText($payload['label']),
            'label_translation_key' => null,
            'position' => $payload['position'],
        ]);

        foreach ($payload['sections'] as $row) {
            $sectionVersion = $this->writer->section($row['section']);

            StructureVersionSection::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
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
        );
    }
}
