<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ListingHasNothingToPublish;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\LifeEventType;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use Illuminate\Support\Str;

class MarketplacePayload
{
    /** @var array<string, array<string, mixed>> */
    private array $valueLists = [];

    /** @var array<int, string> */
    private array $references = [];

    private function __construct() {}

    /** @return array<string, mixed> */
    public static function of(Template|LifeEventType|Section $listable): array
    {
        return new self()->build($listable);
    }

    /** @return array<string, mixed> */
    public static function ofTemplateVersion(Template $template, StructureVersion $version): array
    {
        $payload = new self;
        $described = $payload->template($template, $version);

        return ['type' => 'template', 'template' => $described, 'value_lists' => $payload->valueLists];
    }

    /** @return array<string, mixed> */
    public static function ofLifeEventTypeVersion(LifeEventType $lifeEventType, StructureVersion $version): array
    {
        $payload = new self;
        $described = $payload->lifeEventType($lifeEventType, $version);

        return ['type' => 'life_event_type', 'life_event_type' => $described, 'value_lists' => $payload->valueLists];
    }

    /** @return array<string, mixed> */
    public static function ofSectionVersion(SectionVersion $sectionVersion): array
    {
        $payload = new self;
        $section = $payload->section($sectionVersion);

        return ['type' => 'section', 'section' => $section, 'value_lists' => $payload->valueLists];
    }

    /** @param  array<string, mixed>  $payload */
    public static function checksum(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, mixed> */
    private function build(Template|LifeEventType|Section $listable): array
    {
        $published = $listable instanceof Section
            ? $listable->publishedVersion
            : $listable->structure?->publishedVersion;

        if ($published === null) {
            throw new ListingHasNothingToPublish("{$listable->name} has never been published, so there is nothing frozen to send.");
        }

        if ($listable instanceof Section) {
            $section = $this->section($published);

            return ['type' => 'section', 'section' => $section, 'value_lists' => $this->valueLists];
        }

        if ($listable instanceof LifeEventType) {
            $lifeEventType = $this->lifeEventType($listable);

            return ['type' => 'life_event_type', 'life_event_type' => $lifeEventType, 'value_lists' => $this->valueLists];
        }

        $template = $this->template($listable);

        return ['type' => 'template', 'template' => $template, 'value_lists' => $this->valueLists];
    }

    /** @return array<string, mixed> */
    private function template(Template $template, ?StructureVersion $version = null): array
    {
        $version ??= $template->structure->publishedVersion;

        return [
            'slug' => $template->slug,
            'name' => $template->getRawOriginal('name'),
            'name_translation_key' => $template->name_translation_key,
            'description' => $template->description,
            'applies_to' => $template->structure->applies_to->value,
            'locale' => $template->locale,
            'version_number' => $version->version_number,
            'entity_type' => $this->entityType($template->structure->entityType),
            'pages' => $this->pages($version),
        ];
    }

    /**
     * A life event type travels as itself plus its structure. What somebody installs is a restaurant
     * log, not a structure, so the label, the icon, the colour and the category it was filed under
     * go with the fields.
     *
     * @return array<string, mixed>
     */
    private function lifeEventType(LifeEventType $lifeEventType, ?StructureVersion $version = null): array
    {
        $version ??= $lifeEventType->structure->publishedVersion;

        return [
            'name' => $lifeEventType->getRawOriginal('name'),
            'name_translation_key' => $lifeEventType->name_translation_key,
            'description' => $lifeEventType->description,
            'icon' => $lifeEventType->icon,
            'colour' => $lifeEventType->colour,
            'category' => $lifeEventType->lifeEventCategory->name,
            'category_key' => $lifeEventType->lifeEventCategory->key,
            'version_number' => $version->version_number,
            'pages' => $this->pages($version),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function pages(StructureVersion $version): array
    {
        $assembly = $version->assembly->load('sectionVersion.section', 'sectionVersion.fields.relationEntityType');

        return $version->pages
            ->map(fn (StructureVersionPage $page): array => [
                'key' => $page->key,
                'label' => $page->getRawOriginal('label'),
                'label_translation_key' => $page->label_translation_key,
                'position' => $page->position,
                'sections' => $assembly
                    ->where('structure_version_page_id', $page->id)
                    ->map(fn (StructureVersionSection $row): array => [
                        'position' => $row->position,
                        'title_override' => $row->title_override,
                        'is_collapsed_by_default' => $row->is_collapsed_by_default,
                        'section' => $this->section($row->sectionVersion),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function section(SectionVersion $sectionVersion): array
    {
        $this->collectValueLists($sectionVersion);

        $section = $sectionVersion->section;

        return [
            'slug' => $section->slug,
            'name' => $section->getRawOriginal('name'),
            'name_translation_key' => $section->name_translation_key,
            'description' => $section->description,
            'icon' => $section->icon,
            'colour' => $section->colour,
            'locale' => $section->locale,
            'version_number' => $sectionVersion->version_number,
            'fields' => $sectionVersion->fields
                ->map(fn (Field $field): array => $this->field($field, $sectionVersion))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function field(Field $field, SectionVersion $sectionVersion): array
    {
        $parent = $field->parent_field_id === null
            ? null
            : $sectionVersion->fields->firstWhere('id', $field->parent_field_id);

        return [
            'key' => $field->key,
            'type' => $field->type->value,
            'label' => $field->getRawOriginal('label'),
            'label_translation_key' => $field->label_translation_key,
            'help_text' => $field->help_text,
            'placeholder' => $field->placeholder,
            'position' => $field->position,
            'is_required' => $field->is_required,
            'is_sensitive' => $field->is_sensitive,
            'is_highlighted' => $field->is_highlighted,
            'is_unique' => $field->is_unique,
            'is_repeatable' => $field->is_repeatable,
            'repeat_min' => $field->repeat_min,
            'repeat_max' => $field->repeat_max,
            'default_value' => $field->default_value,
            'options' => $field->options,
            'validation' => $field->validation,
            'parent_field_key' => $parent?->key,
            'value_list_reference' => $field->value_list_id === null ? null : ($this->references[$field->value_list_id] ?? null),
            'relation_target' => $field->relation_target?->value,
            'relation_entity_type' => $this->entityType($field->relationEntityType),
        ];
    }

    /** @return array<string, mixed>|null */
    private function entityType(?EntityType $entityType): ?array
    {
        if ($entityType === null) {
            return null;
        }

        return [
            'slug' => $entityType->slug,
            'name_singular' => $entityType->name_singular,
            'name_plural' => $entityType->name_plural,
            'description' => $entityType->description,
            'icon' => $entityType->icon,
            'colour' => $entityType->colour,
        ];
    }

    private function collectValueLists(SectionVersion $sectionVersion): void
    {
        foreach ($sectionVersion->resolved_value_lists ?? [] as $list) {
            $reference = $this->referenceFor($list);

            $this->valueLists[$reference] = [
                'key' => $list['key'],
                'name' => $list['name'],
                'name_translation_key' => $list['name_translation_key'],
                'items' => array_map(fn (array $item): array => [
                    'key' => $item['key'],
                    'label' => $item['label'] ?? $this->countryNameOf($item),
                    'label_translation_key' => $item['label_translation_key'],
                    'position' => $item['position'],
                    'is_active' => $item['is_active'],
                ], $list['items']),
            ];
        }
    }

    /**
     * A country picked in the settings has neither label nor translation key, and an installer has
     * nothing else to read it by.
     *
     * @param  array<string, mixed>  $item
     */
    private function countryNameOf(array $item): ?string
    {
        if ($item['label_translation_key'] !== null || ! is_string($item['country_code'] ?? null)) {
            return null;
        }

        return CountryCodes::nameOf($item['country_code'], app()->getLocale());
    }

    /** @param  array<string, mixed>  $list */
    private function referenceFor(array $list): string
    {
        $id = (int) $list['id'];

        if (isset($this->references[$id])) {
            return $this->references[$id];
        }

        $base = $list['key'] ?? Str::slug((string) ($list['name'] ?? $list['name_translation_key']));
        $base = $base === '' ? 'value-list' : $base;

        $reference = $base;
        $suffix = 2;

        while (in_array($reference, $this->references, true)) {
            $reference = "{$base}-{$suffix}";
            $suffix++;
        }

        $this->references[$id] = $reference;

        return $reference;
    }
}
