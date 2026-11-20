<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\ValueListSnapshot;
use App\Models\EntityType;
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
 * Give a fresh account one of the templates it starts with, so the first contact or animal somebody
 * writes down has somewhere to go. Basic contact runs inline while the account is created, because
 * the contact standing for the user is written on it; the others are queued, since nobody is looking
 * at them yet. Only Basic contact and the animal templates become a vault default, and never in place
 * of one a vault already has. An animal template joins the account's Animals kind when it holds one.
 * It does nothing when the account already has that template, so running it again is safe.
 */
class CreateDefaultTemplate implements ShouldQueue
{
    use Queueable;

    private Template $template;

    private StructureVersion $version;

    private ?EntityType $entityType = null;

    /** @var array<string, int> */
    private array $valueLists;

    public function __construct(
        public User $user,
        public DefaultTemplateEnum $supplied = DefaultTemplateEnum::BasicContact,
    ) {}

    public function handle(): void
    {
        if ($this->alreadySupplied()) {
            return;
        }

        $this->readValueLists();

        DB::transaction(function (): void {
            $this->createEntityType();
            $this->createTemplate();
            $this->createPages();
            $this->publish();
            if ($this->supplied->isVaultDefault()) {
                $this->seedTheVaultDefaults();
            }
        });

        $this->log();
    }

    private function alreadySupplied(): bool
    {
        return Template::query()
            ->withTrashed()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', true)
            ->where('slug', $this->supplied->value)
            ->exists();
    }

    private function createEntityType(): void
    {
        $type = $this->supplied->entityType();

        if ($type === null) {
            return;
        }

        $this->entityType = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->where('slug', $type['slug'])
            ->first();

        if ($this->entityType instanceof EntityType) {
            return;
        }

        $position = EntityType::query()->where('account_id', $this->user->account_id)->max('position') ?? 0;
        $locale = $this->user->locale;

        $this->entityType = EntityType::query()->create([
            'account_id' => $this->user->account_id,
            'name_singular' => __($type['singular'], locale: $locale),
            'name_plural' => __($type['plural'], locale: $locale),
            'slug' => $type['slug'],
            'description' => __($type['description'], locale: $locale),
            'icon' => $type['icon'],
            'colour' => $type['colour'],
            'position' => $position + 1,
            'is_active' => true,
            'entity_count' => 0,
        ]);
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
        $supplied = $this->supplied;
        $position = Template::query()->where('account_id', $this->user->account_id)->max('position') ?? 0;

        $this->template = Template::query()->create([
            'account_id' => $this->user->account_id,
            'name' => null,
            'name_translation_key' => $supplied->label(),
            'description' => $supplied->description() === null ? null : __($supplied->description(), locale: $this->user->locale),
            'slug' => $supplied->value,
            'is_system' => true,
            'is_active' => true,
            'locale' => config('app.locale'),
            'position' => $position + 1,
        ]);

        $structure = Structure::query()->create([
            'account_id' => $this->user->account_id,
            'owner_type' => Template::class,
            'owner_id' => $this->template->id,
            'applies_to' => $supplied->appliesTo(),
            'entity_type_id' => $this->entityType?->id,
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

        foreach ($this->supplied->pages() as $pageCase) {
            $page = StructureVersionPage::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $this->version->id,
                'key' => $pageCase->key(),
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

        $this->createFields($version, $case->fields(), $case);

        $version->status = VersionStatusEnum::Published;
        $version->resolved_value_lists = ValueListSnapshot::of($version);
        $version->published_at = now();
        $version->save();

        $section->published_version_id = $version->id;
        $section->save();

        return $version;
    }

    /** @param  list<DefaultFieldEnum>  $fields */
    private function createFields(SectionVersion $version, array $fields, ?DefaultSectionEnum $section = null, ?Field $parent = null): void
    {
        $position = 1;

        foreach ($fields as $field) {
            $row = $version->fields()->create([
                'account_id' => $this->user->account_id,
                'type' => $field->type(),
                'key' => $field->value,
                'label' => null,
                'label_translation_key' => $section?->fieldLabel($field) ?? $field->label(),
                'help_text' => null,
                'placeholder' => null,
                'position' => $position,
                'is_required' => $field->isRequired(),
                'is_sensitive' => false,
                'is_highlighted' => $section?->isHighlighted($field) ?? $field->isHighlighted(),
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
                'relation_target' => $field->relationTarget(),
                'relation_entity_type_id' => null,
            ]);

            $this->createFields($version, $field->children(), parent: $row);

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
                VaultDefaultTemplate::query()->firstOrCreate(
                    [
                        'vault_id' => $vault->id,
                        'applies_to' => $this->supplied->appliesTo(),
                        'entity_type_id' => $this->entityType?->id,
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
        )->afterCommit();
    }
}
