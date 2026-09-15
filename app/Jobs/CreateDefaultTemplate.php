<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\ValueListSnapshot;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Give a fresh account the template it starts with, so the first contact somebody writes down has
 * somewhere to go. It is a job rather than part of creating the account, because it writes a couple
 * of dozen rows nobody is looking at yet.
 */
class CreateDefaultTemplate implements ShouldQueue
{
    use Queueable;

    private Template $template;

    private StructureVersion $version;

    /** @var array<string, int> */
    private array $valueLists;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        $this->readValueLists();

        DB::transaction(function (): void {
            $this->createTemplate();
            $this->createPages();
            $this->publish();
            $this->seedTheVaultDefaults();
        });

        $this->log();
    }

    private function readValueLists(): void
    {
        $this->valueLists = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('id', 'key')
            ->all();
    }

    private function createTemplate(): void
    {
        $supplied = DefaultTemplateEnum::BasicContact;

        $this->template = Template::query()->create([
            'account_id' => $this->user->account_id,
            'name' => null,
            'name_translation_key' => $supplied->label(),
            'description' => null,
            'slug' => $supplied->value,
            'is_system' => true,
            'is_active' => true,
            'locale' => config('app.locale'),
            'position' => 1,
        ]);

        $structure = Structure::query()->create([
            'account_id' => $this->user->account_id,
            'owner_type' => Template::class,
            'owner_id' => $this->template->id,
            'applies_to' => $supplied->appliesTo(),
            'entity_type_id' => null,
            'published_version_id' => null,
        ]);

        $this->version = StructureVersion::query()->create([
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
    }

    private function createPages(): void
    {
        $pagePosition = 1;

        foreach (DefaultTemplateEnum::BasicContact->pages() as $pageCase) {
            $page = StructureVersionPage::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $this->version->id,
                'key' => $pageCase->value,
                'label' => null,
                'label_translation_key' => $pageCase->label(),
                'position' => $pagePosition,
            ]);

            $sectionPosition = 1;

            foreach ($pageCase->sections() as $case) {
                $sectionVersion = $this->createSection($case);

                StructureVersionSection::query()->create([
                    'account_id' => $this->user->account_id,
                    'structure_version_id' => $this->version->id,
                    'structure_version_page_id' => $page->id,
                    'section_version_id' => $sectionVersion->id,
                    'position' => $sectionPosition,
                    'title_override' => null,
                    'is_collapsed_by_default' => false,
                ]);

                $sectionVersion->increment('usage_count');
                $sectionPosition++;
            }

            $pagePosition++;
        }
    }

    private function createSection(DefaultSectionEnum $case): SectionVersion
    {
        $section = Section::query()->create([
            'account_id' => $this->user->account_id,
            'name' => null,
            'name_translation_key' => $case->label(),
            'description' => null,
            'slug' => $case->value,
            'icon' => $case->icon(),
            'colour' => $case->colour()->value,
            'is_system' => true,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => config('app.locale'),
        ]);

        $version = SectionVersion::query()->create([
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

        $this->createFields($version, $case->fields());

        $version->status = VersionStatusEnum::Published;
        $version->resolved_value_lists = ValueListSnapshot::of($version);
        $version->published_at = now();
        $version->save();

        $section->published_version_id = $version->id;
        $section->save();

        return $version;
    }

    /** @param  list<DefaultFieldEnum>  $fields */
    private function createFields(SectionVersion $version, array $fields, ?Field $parent = null): void
    {
        $position = 1;

        foreach ($fields as $field) {
            $row = $version->fields()->create([
                'account_id' => $this->user->account_id,
                'type' => $field->type(),
                'key' => $field->value,
                'label' => null,
                'label_translation_key' => $field->label(),
                'help_text' => null,
                'placeholder' => null,
                'position' => $position,
                'is_required' => false,
                'is_sensitive' => false,
                'is_highlighted' => $field->isHighlighted(),
                'is_unique' => false,
                'is_identifying' => $field->isIdentifying(),
                'value_list_id' => $this->valueListId($field),
                'default_value' => null,
                'options' => $field->options(),
                'validation' => null,
                'is_repeatable' => $field->type()->isRepeater(),
                'repetition_order' => $field->repetitionOrder(),
                'repeat_min' => null,
                'repeat_max' => null,
                'parent_field_id' => $parent?->id,
                'relation_target' => null,
                'relation_entity_type_id' => null,
            ]);

            $this->createFields($version, $field->children(), $row);

            $position++;
        }
    }

    private function valueListId(DefaultFieldEnum $field): ?int
    {
        $list = $field->valueList();

        if (! $list instanceof ValueListEnum) {
            return null;
        }

        return $this->valueLists[$list->value] ?? null;
    }

    private function publish(): void
    {
        $this->version->status = VersionStatusEnum::Published;
        $this->version->published_at = now();
        $this->version->save();

        $structure = $this->template->structure;
        $structure->published_version_id = $this->version->id;
        $structure->save();
    }

    private function seedTheVaultDefaults(): void
    {
        Vault::query()
            ->where('account_id', $this->user->account_id)
            ->each(function (Vault $vault): void {
                VaultDefaultTemplate::query()->updateOrCreate(
                    [
                        'vault_id' => $vault->id,
                        'applies_to' => StructureAppliesToEnum::Contact,
                        'entity_type_id' => null,
                    ],
                    [
                        'account_id' => $this->user->account_id,
                        'structure_version_id' => $this->version->id,
                    ],
                );
            });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::DefaultTemplateSeeded,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
