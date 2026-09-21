<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LifeEventTypeEnum;
use App\Enums\DefaultPageEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\ValueListSnapshot;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Give a fresh account the forty natures a life event can be recorded as, each with the custom
 * fields it asks for, so somebody writing down their first lunch or their first phone call finds
 * the words already there.
 *
 * It is a job rather than part of creating the account, because a type carries a structure, a
 * published version, a page, its groups and their fields, and forty of them is several hundred rows
 * nobody is looking at while they are being written.
 */
class CreateDefaultLifeEventTypes implements ShouldQueue
{
    use Queueable;

    /** @var array<string, LifeEventCategory> */
    private array $categories;

    /** @var array<string, int> */
    private array $positions = [];

    private int $valueListPosition;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        $this->readCategories();
        $this->readValueListPosition();

        DB::transaction(function (): void {
            foreach (LifeEventTypeEnum::cases() as $case) {
                $this->createType($case);
            }
        });

        $this->log();
    }

    private function readCategories(): void
    {
        $this->categories = LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->get()
            ->keyBy('key')
            ->all();
    }

    private function readValueListPosition(): void
    {
        $this->valueListPosition = (int) ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->max('position');
    }

    private function createType(LifeEventTypeEnum $case): void
    {
        $category = $this->categories[$case->category()->value] ?? null;

        if (! $category instanceof LifeEventCategory) {
            return;
        }

        $key = $case->category()->value;
        $this->positions[$key] = ($this->positions[$key] ?? 0) + 1;

        $lifeEventType = LifeEventType::query()->create([
            'account_id' => $this->user->account_id,
            'life_event_category_id' => $category->id,
            'key' => $case->value,
            'name' => null,
            'name_translation_key' => $case->label(),
            'description' => null,
            'icon' => null,
            'colour' => null,
            'is_system' => true,
            'is_active' => true,
            'position' => $this->positions[$key],
        ]);

        $structure = Structure::query()->create([
            'account_id' => $this->user->account_id,
            'owner_type' => LifeEventType::class,
            'owner_id' => $lifeEventType->id,
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

        $page = StructureVersionPage::query()->create([
            'account_id' => $this->user->account_id,
            'structure_version_id' => $version->id,
            'key' => StructureVersionPage::SUPPLIED_KEY,
            'label' => null,
            'label_translation_key' => DefaultPageEnum::General->label(),
            'position' => 1,
        ]);

        $this->createBlocks($case, $version, $page);

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $structure->published_version_id = $version->id;
        $structure->save();
    }

    private function createBlocks(LifeEventTypeEnum $case, StructureVersion $version, StructureVersionPage $page): void
    {
        $position = 1;

        foreach ($case->blocks() as $block) {
            $sectionVersion = $this->createSection($case, $block);

            StructureVersionSection::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position,
                'title_override' => null,
                'shows_title' => $block['name'] !== null,
                'is_collapsed_by_default' => false,
            ]);

            $sectionVersion->increment('usage_count');
            $position++;
        }
    }

    /**
     * @param  array{name: ?string, fields: list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>}  $block
     */
    private function createSection(LifeEventTypeEnum $case, array $block): SectionVersion
    {
        $section = Section::query()->create([
            'account_id' => $this->user->account_id,
            'name' => null,
            'name_translation_key' => $block['name'] ?? 'Fields in no group',
            'description' => null,
            'slug' => $this->slug($case, $block['name']),
            'icon' => null,
            'colour' => null,
            'is_system' => true,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => config('app.locale'),
        ]);

        $sectionVersion = SectionVersion::query()->create([
            'account_id' => $this->user->account_id,
            'section_id' => $section->id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'resolved_value_lists' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => null,
            'usage_count' => 0,
        ]);

        $this->createFields($sectionVersion, $block['fields']);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->resolved_value_lists = ValueListSnapshot::of($sectionVersion);
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        return $sectionVersion;
    }

    private function slug(LifeEventTypeEnum $case, ?string $name): string
    {
        return 'life-event-'.Str::slug($case->value).'-'.Str::slug($name ?? 'ungrouped');
    }

    /**
     * @param  list<array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}>  $fields
     */
    private function createFields(SectionVersion $sectionVersion, array $fields): void
    {
        $position = 1;

        foreach ($fields as $field) {
            $sectionVersion->fields()->create([
                'account_id' => $this->user->account_id,
                'type' => $field['type'],
                'key' => $field['key'],
                'label' => null,
                'label_translation_key' => $field['label'],
                'help_text' => null,
                'placeholder' => null,
                'position' => $position,
                'is_required' => false,
                'is_sensitive' => false,
                'is_highlighted' => false,
                'is_unique' => false,
                'is_identifying' => false,
                'value_list_id' => $this->valueListFor($field)?->id,
                'default_value' => null,
                'options' => $field['options'],
                'validation' => null,
                'is_repeatable' => false,
                'repetition_order' => null,
                'repeat_min' => null,
                'repeat_max' => null,
                'parent_field_id' => null,
                'relation_target' => null,
                'relation_entity_type_id' => null,
            ]);

            $position++;
        }
    }

    /**
     * The choices of a field that offers them live in a list of that field alone: unshared, so it
     * is listed on no screen and offered in no picker, exactly as the field editor writes one.
     *
     * @param  array{key: string, label: string, type: FieldTypeEnum, options: ?array<string, int|string>, choices: list<string>}  $field
     */
    private function valueListFor(array $field): ?ValueList
    {
        if (! $field['type']->usesValueList()) {
            return null;
        }

        $this->valueListPosition++;

        $list = ValueList::query()->create([
            'account_id' => $this->user->account_id,
            'key' => null,
            'name' => null,
            'name_translation_key' => $field['label'],
            'description' => null,
            'is_system' => true,
            'allows_user_values' => true,
            'is_shared' => false,
            'position' => $this->valueListPosition,
        ]);

        $position = 1;
        $rows = [];

        foreach ($field['choices'] as $choice) {
            $rows[] = [
                'account_id' => $this->user->account_id,
                'key' => Str::slug($choice, '_'),
                'label' => null,
                'label_translation_key' => $choice,
                'position' => $position,
                'is_system' => true,
                'is_active' => true,
                'usage_count' => 0,
            ];

            $position++;
        }

        $list->items()->createMany($rows);

        return $list;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypesSeeded,
        )->onQueue('low');
    }
}
