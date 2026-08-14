<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ListingHasNothingToPublish;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use Illuminate\Support\Str;

/**
 * Writes down everything an account needs to build its own copy of something
 * published to the marketplace.
 *
 * The payload is self contained, and that is the whole difficulty. Installing
 * is copying, and the copy has to stand alone: it can never reach back into the
 * account of the author, which may have moved on, been deleted, or never have
 * been reachable in the first place. So nothing here is an identifier from
 * somewhere else. A field names its value list by a reference resolved inside
 * the payload, a nested field names its parent by key, and a relation carries
 * the kind of record it points at rather than a row number nobody else has.
 *
 * What is read is the frozen version and never the live structure. For a
 * template that means the section versions its assembly pins, which are not
 * necessarily what those sections have published since, and the value lists
 * those versions were frozen with. A catalogue that quietly followed the latest
 * state of somebody's account would offer something nobody moderated.
 */
class MarketplacePayload
{
    /**
     * The lists the fields point at, by the reference they are named by.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $valueLists = [];

    /**
     * The reference each list of the author was given, by its id there. The id
     * never travels; it is only how the same list is recognised across the
     * several sections of one template.
     *
     * @var array<int, string>
     */
    private array $references = [];

    private function __construct() {}

    /**
     * Write down what is published, which is a template with its pages or a
     * section on its own.
     *
     * @return array<string, mixed>
     */
    public static function of(Template|Section $listable): array
    {
        return new self()->build($listable);
    }

    /**
     * Write down one precise state of a section, rather than whatever that
     * section has published since.
     *
     * A template version pins the section version it carries, and that pinned
     * one is what the builder shows and what a record renders. It can be older
     * than the section's own published version, so anything writing down what
     * is on the screen has to name the version rather than the section.
     *
     * @return array<string, mixed>
     */
    public static function ofSectionVersion(SectionVersion $sectionVersion): array
    {
        $payload = new self;
        $section = $payload->section($sectionVersion);

        return ['type' => 'section', 'section' => $section, 'value_lists' => $payload->valueLists];
    }

    /**
     * Hash a payload, so an install can prove it got what was approved.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function checksum(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Template|Section $listable): array
    {
        if ($listable->publishedVersion === null) {
            throw new ListingHasNothingToPublish("{$listable->name} has never been published, so there is nothing frozen to send.");
        }

        if ($listable instanceof Section) {
            $section = $this->section($listable->publishedVersion);

            return ['type' => 'section', 'section' => $section, 'value_lists' => $this->valueLists];
        }

        $template = $this->template($listable);

        return ['type' => 'template', 'template' => $template, 'value_lists' => $this->valueLists];
    }

    /**
     * @return array<string, mixed>
     */
    private function template(Template $template): array
    {
        $version = $template->publishedVersion;
        $assembly = $version->assembly->load('sectionVersion.section', 'sectionVersion.fields.relationEntityType');

        $pages = $version->pages
            ->map(fn (TemplateVersionPage $page): array => [
                'key' => $page->key,
                'label' => $page->getRawOriginal('label'),
                'label_translation_key' => $page->label_translation_key,
                'position' => $page->position,
                'sections' => $assembly
                    ->where('template_version_page_id', $page->id)
                    ->map(fn (TemplateVersionSection $row): array => [
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

        return [
            'slug' => $template->slug,
            'name' => $template->getRawOriginal('name'),
            'name_translation_key' => $template->name_translation_key,
            'description' => $template->description,
            'applies_to' => $template->applies_to->value,
            'locale' => $template->locale,
            'version_number' => $version->version_number,
            'entity_type' => $this->entityType($template->entityType),
            'pages' => $pages,
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
            'locale' => $section->locale,
            'version_number' => $sectionVersion->version_number,
            'fields' => $sectionVersion->fields
                ->map(fn (Field $field): array => $this->field($field, $sectionVersion))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
            'is_reminder_source' => $field->is_reminder_source,
            'default_value' => $field->default_value,
            'options' => $field->options,
            'validation' => $field->validation,
            'parent_field_key' => $parent?->key,
            'value_list_reference' => $field->value_list_id === null ? null : ($this->references[$field->value_list_id] ?? null),
            'relation_target' => $field->relation_target?->value,
            'relation_entity_type' => $this->entityType($field->relationEntityType),
        ];
    }

    /**
     * Write down the kind of record something points at, since an entity type
     * invented in one account exists nowhere else.
     *
     * @return array<string, mixed>|null
     */
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

    /**
     * Take the lists this section version was frozen with, rather than the ones
     * its account holds today. They were copied at publication precisely so
     * that somebody renaming an option later cannot change what was published,
     * and that copy is what travels.
     */
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
                    'label' => $item['label'],
                    'label_translation_key' => $item['label_translation_key'],
                    'position' => $item['position'],
                    'is_active' => $item['is_active'],
                ], $list['items']),
            ];
        }
    }

    /**
     * Name a list inside the payload. A list the product supplies has a key
     * everywhere, which is what lets an installer recognise their own
     * "Countries"; one somebody invented has none, so it is named after itself.
     *
     * @param  array<string, mixed>  $list
     */
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
