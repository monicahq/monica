<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Helpers\StructureWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Read a life event type out of a file and build it here.
 *
 * What arrives is a copy and never a link, so nothing about it follows the original afterwards. The
 * category travels as the key of a supplied category and as a name, never as an identifier: the
 * account that installs it decides which of its own categories receives the type, matching the key
 * first because a name reads differently in every language, and falls back to Other when nothing
 * matches.
 */
class ImportLifeEventType
{
    private LifeEventType $lifeEventType;

    /** @var array<string, mixed> */
    private array $file;

    private StructureWriter $writer;

    public function __construct(
        private readonly User $user,
        private readonly string $raw,
        private readonly ?LifeEventCategory $lifeEventCategory = null,
    ) {}

    /** @throws StructureFileIsNotReadable */
    public function execute(): LifeEventType
    {
        $this->validate();
        $this->import();
        $this->log();

        return $this->lifeEventType;
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory !== null && $this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }

        $this->file = StructureFileReader::read($this->raw, expecting: 'life_event_type');
    }

    private function import(): void
    {
        $payload = $this->file['life_event_type'];

        DB::transaction(function () use ($payload): void {
            $this->writer = new StructureWriter($this->user, $this->file);
            $this->writer->settleTheValueLists();

            $category = $this->category($payload['category_key'], $payload['category']);

            $positions = LifeEventType::query()
                ->where('life_event_category_id', $category->id)
                ->pluck('position');

            $this->lifeEventType = LifeEventType::query()->create([
                'account_id' => $this->user->account_id,
                'life_event_category_id' => $category->id,
                'key' => null,
                'name' => TextSanitizer::plainText($payload['name']),
                'name_translation_key' => null,
                'description' => TextSanitizer::nullablePlainText($payload['description']),
                'icon' => $payload['icon'],
                'colour' => $payload['colour'],
                'is_system' => false,
                'is_active' => true,
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $structure = Structure::query()->create([
                'account_id' => $this->user->account_id,
                'owner_type' => LifeEventType::class,
                'owner_id' => $this->lifeEventType->id,
                'applies_to' => StructureAppliesToEnum::LifeEvent,
                'entity_type_id' => null,
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

            new PublishStructureVersion(user: $this->user, structureVersion: $version)->execute();
        });
    }

    private function category(?string $key, ?string $name): LifeEventCategory
    {
        if ($this->lifeEventCategory !== null) {
            return $this->lifeEventCategory;
        }

        $categories = LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', true)
            ->get();

        return $categories->first(fn (LifeEventCategory $category): bool => $key !== null && $category->key === $key)
            ?? $categories->first(fn (LifeEventCategory $category): bool => $category->name === $name)
            ?? $categories->first(fn (LifeEventCategory $category): bool => $category->key === 'other')
            ?? $categories->firstOrFail();
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

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypeCreation,
            parameters: ['name' => $this->lifeEventType->name],
            loggable: $this->lifeEventType,
        )->onQueue('low');
    }
}
