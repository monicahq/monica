<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\StructureVersionPage;
use App\Models\Template;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use JsonException;

/**
 * The gate a pasted template or section has to get through before it becomes
 * anything.
 *
 * Somebody who has arranged a template can export it as a JSON file
 * ({@see StructureFile}) and carry it to another vault, to another instance, or to
 * another person. Whoever receives it pastes it into the import screen, and that
 * screen asks this to read it: they are told whether the file can be imported
 * before anything is created, and the structure then arrives as a draft holding
 * none of the records the original had.
 *
 * It hands back the same block every time, for ImportTemplate and ImportSection to
 * build from. A file it cannot read is refused at the first thing that is wrong,
 * naming the position in the file, because the person who pasted it is the one who
 * has to fix it.
 *
 * This walks the document and decides what it means; {@see StructureFileValues}
 * reads the values out of it.
 */
class StructureFileReader
{
    public const int MAX_BYTES = 524288;

    public const int MAX_DEPTH = 32;

    public const int MAX_PAGES = 25;

    public const int MAX_SECTIONS_PER_PAGE = 50;

    public const int MAX_FIELDS_PER_SECTION = 200;

    public const int MAX_FIELDS = 1000;

    public const int MAX_VALUE_LISTS = 100;

    public const int MAX_ITEMS_PER_LIST = 500;

    public const int MAX_MIME_TYPES = 20;

    public const int MAX_DEFAULT_VALUES = 50;

    private const string COLOUR = '/^#[0-9a-f]{6}$/';

    private const string MIME_TYPE = '#^[a-z0-9!\#$&^_.+-]+/[a-z0-9!\#$&^_.+-]+$#';

    private int $fieldCount = 0;

    /** @var array<string, array<string, mixed>> */
    private array $valueLists = [];

    private int $formatVersion = StructureFile::FORMAT_VERSION;

    /** @param  array<string, mixed>  $file */
    private function __construct(private readonly array $file) {}

    /**
     * @return array<string, mixed>
     *
     * @throws StructureFileIsNotReadable
     */
    public static function read(string $raw, ?string $expecting = null): array
    {
        $read = new self(self::decode($raw))->check();

        if ($expecting === null || $expecting === $read['format']) {
            return $read;
        }

        if ($read['format'] === 'life_event_type') {
            throw new StructureFileIsNotReadable(__('This file holds a life event type. Import it from the list of life event types and it arrives as a copy of its own.'));
        }

        if ($expecting === 'template') {
            throw new StructureFileIsNotReadable(__('This file holds one section rather than a whole template. Open the template you want it on, and import it from the page it belongs on.'));
        }

        if ($expecting === 'life_event_type') {
            throw new StructureFileIsNotReadable(__('This file does not hold a life event type. Import it where it belongs and it arrives as a copy of its own.'));
        }

        throw new StructureFileIsNotReadable(__('This file holds a whole template rather than one section. Import it from the list of templates and it arrives as a draft of its own.'));
    }

    /** @return array<string, mixed> */
    private static function decode(string $raw): array
    {
        if (mb_trim($raw) === '') {
            throw new StructureFileIsNotReadable(__('There is nothing here to read. Paste the file, outer braces included.'));
        }

        if (strlen($raw) > self::MAX_BYTES) {
            throw new StructureFileIsNotReadable(__('This is larger than a structure file ever is, so it was not read at all. The limit is :size.', [
                'size' => Number::fileSize(self::MAX_BYTES),
            ]));
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new StructureFileIsNotReadable(__('This is not text in an encoding that can be read. A structure file is UTF-8.'));
        }

        try {
            $decoded = json_decode($raw, true, self::MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new StructureFileIsNotReadable(__('This is not readable as JSON. Something is missing, repeated or nested deeper than a structure file allows.'));
        }

        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new StructureFileIsNotReadable(__('This reads as JSON, but a structure file is a block starting with a brace rather than a list or a single value.'));
        }

        return $decoded;
    }

    /** @return array<string, mixed> */
    private function check(): array
    {
        $format = $this->header();

        $allowed = match ($format) {
            'template' => ['monica', 'exported_at', 'template', 'value_lists'],
            'life_event_type' => ['monica', 'exported_at', 'life_event_type', 'value_lists'],
            default => ['monica', 'exported_at', 'from_template', 'section', 'value_lists'],
        };

        $file = StructureFileValues::object($this->file, 'the file', $allowed, ['monica', $format]);

        $this->readTheValueLists();

        return [
            'format' => $format,
            'format_version' => $this->formatVersion,
            'exported_at' => StructureFileValues::nullableString($file, 'exported_at', 'the file', 64),
            'from_template' => $format === 'section' ? $this->fromTemplate($file) : null,
            'value_lists' => $this->valueLists,
            'template' => $format === 'template' ? $this->template($file['template']) : null,
            'life_event_type' => $format === 'life_event_type' ? $this->lifeEventType($file['life_event_type']) : null,
            'section' => $format === 'section' ? $this->section($file['section'], 'section') : null,
        ];
    }

    private function header(): string
    {
        $node = $this->file['monica'] ?? null;

        if (! is_array($node) || $node === []) {
            $this->refuse(__('There is no Monica header in this file, so there is no saying what it holds. A file exported from Monica starts with `"monica": { "format": … }`.'));
        }

        $header = StructureFileValues::object($node, 'monica', ['format', 'format_version'], ['format', 'format_version']);

        $format = StructureFileValues::string($header, 'format', 'monica', 32);

        if ($format !== 'template' && $format !== 'section' && $format !== 'life_event_type') {
            $this->refuse(__('The header says :format. Monica reads three kinds of file: a template, a life event type and a section.', [
                'format' => $this->safe($format),
            ]));
        }

        $this->formatVersion = StructureFileValues::integer($header, 'format_version', 'monica', 1, 9999);

        if ($this->formatVersion > StructureFile::FORMAT_VERSION) {
            $this->refuse(__('This file was written in format :written, and this account reads format :read. Ask for an export from a Monica of this version, or update yours.', [
                'written' => $this->formatVersion,
                'read' => StructureFile::FORMAT_VERSION,
            ]));
        }

        return $format;
    }

    /**
     * @param  array<string, mixed>  $file
     * @return array<string, mixed>|null
     */
    private function fromTemplate(array $file): ?array
    {
        if (($file['from_template'] ?? null) === null) {
            return null;
        }

        $from = StructureFileValues::object($file['from_template'], 'from_template', ['name', 'version'], []);

        return [
            'name' => StructureFileValues::nullableString($from, 'name', 'from_template', 255),
            'version' => StructureFileValues::nullableInteger($from, 'version', 'from_template', 1, 65535),
        ];
    }

    /** @return array<string, mixed> */
    /**
     * A life event type carries what it looks like alongside its fields, so an installer gets a
     * restaurant log rather than a bare structure. The category is read as a name and matched
     * against the account's own when the copy is made.
     *
     * @return array<string, mixed>
     */
    private function lifeEventType(mixed $node): array
    {
        $lifeEventType = StructureFileValues::object($node, 'life_event_type', [
            'name', 'name_translation_key', 'description', 'icon', 'colour',
            'category', 'category_key', 'version_number', 'pages',
        ], ['pages']);

        $pages = $this->pages($lifeEventType['pages'], 'life_event_type.pages');

        if ($pages === []) {
            $this->refuse(__('The header is right, but the life event type holds no page, so there is nothing in it to build.'));
        }

        $icon = StructureFileValues::identifier($lifeEventType, 'icon', 'life_event_type', 64);

        if ($icon !== null && ! in_array($icon, Section::ICONS, true)) {
            $this->refuse(__('The life event type in this file asks for an icon this version does not have.'));
        }

        $colour = StructureFileValues::nullableString($lifeEventType, 'colour', 'life_event_type', 7);

        if ($colour !== null && ! in_array(mb_strtolower($colour), VaultColourEnum::values(), true)) {
            $this->refuse(__('The life event type in this file asks for a colour this version does not offer.'));
        }

        return [
            'name' => StructureFileValues::word($lifeEventType, 'name', 'name_translation_key', 'life_event_type', 255),
            'description' => StructureFileValues::nullableString($lifeEventType, 'description', 'life_event_type', 255),
            'icon' => $icon,
            'colour' => $colour === null ? null : mb_strtolower($colour),
            'category' => StructureFileValues::nullableString($lifeEventType, 'category', 'life_event_type', 255),
            'category_key' => StructureFileValues::identifier($lifeEventType, 'category_key', 'life_event_type', 64),
            'version_number' => StructureFileValues::integer($lifeEventType, 'version_number', 'life_event_type', 1, 9999, 1),
            'pages' => $pages,
        ];
    }

    /** @return array<string, mixed> */
    private function template(mixed $node): array
    {
        $template = StructureFileValues::object($node, 'template', [
            'slug', 'name', 'name_translation_key', 'description', 'applies_to', 'locale',
            'version_number', 'entity_type', 'pages',
        ], ['pages']);

        $appliesTo = StructureFileValues::string($template, 'applies_to', 'template', 32, StructureAppliesToEnum::Contact->value);

        if (StructureAppliesToEnum::tryFrom($appliesTo) === null) {
            $this->refuse(__('The template says it applies to :word, which is not something this version knows about.', [
                'word' => $this->safe($appliesTo),
            ]));
        }

        $entityType = $this->entityType($template['entity_type'] ?? null, 'template.entity_type');

        if ($appliesTo === StructureAppliesToEnum::Entity->value && $entityType === null) {
            $this->refuse(__('The template says it describes a kind of record, but it does not say which one.'));
        }

        $pages = $this->pages($template['pages']);

        if ($pages === []) {
            $this->refuse(__('The header is right, but the template holds no page, so there is nothing in it to build.'));
        }

        return [
            'slug' => StructureFileValues::identifier($template, 'slug', 'template', Template::SLUG_LENGTH),
            'name' => StructureFileValues::word($template, 'name', 'name_translation_key', 'template', 255),
            'description' => StructureFileValues::nullableString($template, 'description', 'template', 65535),
            'applies_to' => $appliesTo,
            'locale' => StructureFileValues::locale($template, 'template'),
            'entity_type' => $appliesTo === StructureAppliesToEnum::Entity->value ? $entityType : null,
            'pages' => $pages,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function pages(mixed $node, string $root = 'template.pages'): array
    {
        $rows = StructureFileValues::collection($node, $root, self::MAX_PAGES);
        $keys = [];
        $pages = [];

        foreach ($rows as $index => $row) {
            $path = "{$root}.{$index}";

            $page = StructureFileValues::object($row, $path, ['key', 'label', 'label_translation_key', 'position', 'sections'], ['sections']);
            $label = StructureFileValues::word($page, 'label', 'label_translation_key', $path, 255);
            $key = StructureFileValues::identifier($page, 'key', $path, StructureVersionPage::KEY_LENGTH) ?? Str::slug($label);
            $key = $key === '' ? 'page' : $key;

            if (in_array($key, $keys, true)) {
                $this->refuse(__('Two pages of this template carry the same key, so one of them would take the place of the other.'));
            }

            $keys[] = $key;

            $pages[] = [
                'key' => $key,
                'label' => $label,
                'position' => StructureFileValues::integer($page, 'position', $path, 0, 65535, $index + 1),
                'sections' => $this->assembly($page['sections'], $path),
            ];
        }

        return $pages;
    }

    /** @return list<array<string, mixed>> */
    private function assembly(mixed $node, string $parentPath): array
    {
        $rows = StructureFileValues::collection($node, "{$parentPath}.sections", self::MAX_SECTIONS_PER_PAGE);
        $assembly = [];

        foreach ($rows as $index => $row) {
            $path = "{$parentPath}.sections.{$index}";

            $placement = StructureFileValues::object($row, $path, ['position', 'title_override', 'is_collapsed_by_default', 'section'], ['section']);

            $assembly[] = [
                'position' => StructureFileValues::integer($placement, 'position', $path, 0, 65535, $index + 1),
                'title_override' => StructureFileValues::nullableString($placement, 'title_override', $path, 255),
                'is_collapsed_by_default' => StructureFileValues::boolean($placement, 'is_collapsed_by_default', $path),
                'section' => $this->section($placement['section'], "{$path}.section"),
            ];
        }

        return $assembly;
    }

    /** @return array<string, mixed> */
    private function section(mixed $node, string $path): array
    {
        $section = StructureFileValues::object($node, $path, [
            'slug', 'name', 'name_translation_key', 'description', 'icon', 'colour', 'locale',
            'version_number', 'fields',
        ], ['fields']);

        $fields = $this->fields($section['fields'], $path);

        if ($fields === []) {
            $this->refuse(__('The header is right, but a section in this file holds no field, so there is nothing in it to build.'));
        }

        $icon = StructureFileValues::identifier($section, 'icon', $path, 64);

        if ($icon !== null && ! in_array($icon, Section::ICONS, true)) {
            $this->refuse(__('A section in this file asks for an icon this version does not have.'));
        }

        $colour = StructureFileValues::nullableString($section, 'colour', $path, 7);

        if ($colour !== null && ! in_array(mb_strtolower($colour), VaultColourEnum::values(), true)) {
            $this->refuse(__('A section in this file asks for a colour this version does not offer.'));
        }

        return [
            'slug' => StructureFileValues::identifier($section, 'slug', $path, Section::SLUG_LENGTH),
            'name' => StructureFileValues::word($section, 'name', 'name_translation_key', $path, 255),
            'description' => StructureFileValues::nullableString($section, 'description', $path, 65535),
            'icon' => $icon,
            'colour' => $colour === null ? null : mb_strtolower($colour),
            'locale' => StructureFileValues::locale($section, $path),
            'fields' => $fields,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fields(mixed $node, string $parentPath): array
    {
        $rows = StructureFileValues::collection($node, "{$parentPath}.fields", self::MAX_FIELDS_PER_SECTION);

        $this->fieldCount += count($rows);

        if ($this->fieldCount > self::MAX_FIELDS) {
            $this->refuse(trans_choice(
                'This file describes more than :count field, which is more structure than one file is meant to carry.|This file describes more than :count fields, which is more structure than one file is meant to carry.',
                self::MAX_FIELDS,
            ));
        }

        $fields = [];
        $keys = [];

        foreach ($rows as $index => $row) {
            $field = $this->field($row, "{$parentPath}.fields.{$index}", $index);

            if (in_array($field['key'], $keys, true)) {
                $this->refuse(__('Two fields of one section carry the same key, so a value written into either would have nowhere to go.'));
            }

            $keys[] = $field['key'];
            $fields[] = $field;
        }

        $this->checkTheRepeaters($fields);

        return $fields;
    }

    /** @return array<string, mixed> */
    private function field(mixed $node, string $path, int $index): array
    {
        $field = StructureFileValues::object($node, $path, [
            'key', 'type', 'label', 'label_translation_key', 'help_text', 'placeholder', 'position',
            'is_required', 'is_sensitive', 'is_highlighted', 'is_unique', 'is_repeatable',
            'repeat_min', 'repeat_max', 'is_reminder_source', 'default_value', 'options', 'validation',
            'parent_field_key', 'value_list_reference', 'relation_target', 'relation_entity_type',
        ], ['type']);

        $word = StructureFileValues::string($field, 'type', $path, 32);
        $type = FieldTypeEnum::tryFrom($word);

        if ($type === null) {
            $this->refuse(__('A field in this file is of the kind :word, which this version does not know how to render.', [
                'word' => $this->safe($word),
            ]));
        }

        $label = StructureFileValues::word($field, 'label', 'label_translation_key', $path, 255);
        $key = StructureFileValues::identifier($field, 'key', $path, Field::KEY_LENGTH) ?? Str::slug($label, '_');
        $reference = StructureFileValues::identifier($field, 'value_list_reference', $path, 64);

        if ($reference !== null && ! isset($this->valueLists[$reference])) {
            $this->refuse(__('A field in this file offers choices from a list the file does not carry.'));
        }

        if ($type->usesValueList() && $reference === null) {
            $this->refuse(__('A field in this file offers choices without saying which list they come from.'));
        }

        if (! $type->usesValueList() && $reference !== null) {
            $this->refuse(__('A field in this file names a value list although its kind offers none.'));
        }

        $relationTarget = $this->relationTarget($field, $path, $type);
        $relationEntityType = $this->entityType($field['relation_entity_type'] ?? null, "{$path}.relation_entity_type");

        if (! $type->isRelation() && $relationEntityType !== null) {
            $this->refuse(__('A field in this file points at a kind of record although it is not a relation.'));
        }

        if ($relationTarget === RelationTargetEnum::Entity->value && $relationEntityType === null) {
            $this->refuse(__('A relation in this file points at a kind of record without saying which one.'));
        }

        $isRepeatable = StructureFileValues::boolean($field, 'is_repeatable', $path, $type->isRepeater());

        if ($isRepeatable !== $type->isRepeater()) {
            $this->refuse(__('A field in this file says it repeats although its kind does not, or the other way round.'));
        }

        $repeatMin = $type->isRepeater() ? StructureFileValues::nullableInteger($field, 'repeat_min', $path, 0, 100) : null;
        $repeatMax = $type->isRepeater() ? StructureFileValues::nullableInteger($field, 'repeat_max', $path, 0, 100) : null;

        if ($repeatMin !== null && $repeatMax !== null && $repeatMin > $repeatMax) {
            $this->refuse(__('A repeater in this file asks for more repetitions at least than it allows at most.'));
        }

        return [
            'key' => $key === '' ? 'field' : $key,
            'type' => $type->value,
            'label' => $label,
            'help_text' => StructureFileValues::nullableString($field, 'help_text', $path, 255),
            'placeholder' => StructureFileValues::nullableString($field, 'placeholder', $path, 255),
            'position' => StructureFileValues::integer($field, 'position', $path, 0, 65535, $index + 1),
            'is_required' => StructureFileValues::boolean($field, 'is_required', $path),
            'is_sensitive' => StructureFileValues::boolean($field, 'is_sensitive', $path),
            'is_highlighted' => StructureFileValues::boolean($field, 'is_highlighted', $path),
            'is_unique' => StructureFileValues::boolean($field, 'is_unique', $path),
            'is_repeatable' => $isRepeatable,
            'repeat_min' => $repeatMin,
            'repeat_max' => $repeatMax,
            'default_value' => $type->usesValueList() ? null : $this->defaultValue($field, $path),
            'options' => $this->options($field, $path),
            'parent_field_key' => StructureFileValues::identifier($field, 'parent_field_key', $path, Field::KEY_LENGTH),
            'value_list_reference' => $reference,
            'relation_target' => $relationTarget,
            'relation_entity_type' => $type->isRelation() ? $relationEntityType : null,
        ];
    }

    /** @param  list<array<string, mixed>>  $fields */
    private function checkTheRepeaters(array $fields): void
    {
        $repeaters = [];

        foreach ($fields as $field) {
            if ($field['type'] === FieldTypeEnum::Repeater->value) {
                $repeaters[] = $field['key'];
            }
        }

        foreach ($fields as $field) {
            $parent = $field['parent_field_key'];

            if ($parent === null) {
                continue;
            }

            if ($parent === $field['key']) {
                $this->refuse(__('A field in this file sits inside itself.'));
            }

            if (! in_array($parent, $repeaters, true)) {
                $this->refuse(__('A field in this file sits inside something that is not a repeater of the same section.'));
            }

            if ($field['type'] === FieldTypeEnum::Repeater->value) {
                $this->refuse(__('A repeater in this file sits inside another repeater, which is not something a record can be filled in with.'));
            }
        }
    }

    private function readTheValueLists(): void
    {
        $node = $this->file['value_lists'] ?? [];

        if (! is_array($node)) {
            $this->refuse(__('The value lists in this file are not written as a block.'));
        }

        if (count($node) > self::MAX_VALUE_LISTS) {
            $this->refuse(trans_choice(
                'This file carries more than :count value list.|This file carries more than :count value lists.',
                self::MAX_VALUE_LISTS,
            ));
        }

        foreach ($node as $name => $row) {
            $reference = (string) $name;

            if (mb_strlen($reference) > 64 || preg_match(StructureFileValues::IDENTIFIER, $reference) !== 1) {
                $this->refuse(__('A value list in this file is named in a way that is not a reference.'));
            }

            $path = "value_lists.{$reference}";
            $list = StructureFileValues::object($row, $path, ['key', 'name', 'name_translation_key', 'items'], ['items']);

            $this->valueLists[$reference] = [
                'key' => StructureFileValues::identifier($list, 'key', $path, 64),
                'name' => StructureFileValues::word($list, 'name', 'name_translation_key', $path, 255),
                'items' => $this->valueListItems($list['items'], $path),
            ];
        }
    }

    /** @return list<array<string, mixed>> */
    private function valueListItems(mixed $node, string $parentPath): array
    {
        $rows = StructureFileValues::collection($node, "{$parentPath}.items", self::MAX_ITEMS_PER_LIST);
        $items = [];
        $keys = [];

        foreach ($rows as $index => $row) {
            $path = "{$parentPath}.items.{$index}";

            $item = StructureFileValues::object($row, $path, ['key', 'label', 'label_translation_key', 'position', 'is_active'], []);
            $key = StructureFileValues::identifier($item, 'key', $path, 64);

            if ($key !== null && in_array($key, $keys, true)) {
                $this->refuse(__('Two choices of one list carry the same key.'));
            }

            if ($key !== null) {
                $keys[] = $key;
            }

            $items[] = [
                'key' => $key,
                'label' => StructureFileValues::word($item, 'label', 'label_translation_key', $path, 255),
                'position' => StructureFileValues::integer($item, 'position', $path, 0, 65535, $index + 1),
                'is_active' => StructureFileValues::boolean($item, 'is_active', $path, true),
            ];
        }

        if ($items === []) {
            $this->refuse(__('A value list in this file holds nothing to choose from.'));
        }

        return $items;
    }

    /** @return array<string, mixed>|null */
    private function entityType(mixed $node, string $path): ?array
    {
        if ($node === null) {
            return null;
        }

        $entityType = StructureFileValues::object($node, $path, ['slug', 'name_singular', 'name_plural', 'description', 'icon', 'colour'], ['name_singular', 'name_plural']);

        $icon = StructureFileValues::identifier($entityType, 'icon', $path, 64);

        if ($icon !== null && ! in_array($icon, EntityType::ICONS, true)) {
            $this->refuse(__('A kind of record in this file asks for an icon this version does not have.'));
        }

        $colour = StructureFileValues::nullableString($entityType, 'colour', $path, 7);

        if ($colour !== null && preg_match(self::COLOUR, mb_strtolower($colour)) !== 1) {
            $this->refuse(__('A kind of record in this file asks for a colour that is not written as a hexadecimal one.'));
        }

        $nameSingular = StructureFileValues::string($entityType, 'name_singular', $path, 255);
        $slug = StructureFileValues::identifier($entityType, 'slug', $path, EntityType::SLUG_LENGTH) ?? Str::slug($nameSingular);

        return [
            'slug' => $slug === '' ? 'kind' : $slug,
            'name_singular' => $nameSingular,
            'name_plural' => StructureFileValues::string($entityType, 'name_plural', $path, 255),
            'description' => StructureFileValues::nullableString($entityType, 'description', $path, 65535),
            'icon' => $icon,
            'colour' => $colour === null ? null : mb_strtolower($colour),
        ];
    }

    /** @param  array<string, mixed>  $field */
    private function relationTarget(array $field, string $path, FieldTypeEnum $type): ?string
    {
        $word = StructureFileValues::nullableString($field, 'relation_target', $path, 32);

        if ($word !== null && RelationTargetEnum::tryFrom($word) === null) {
            $this->refuse(__('A relation in this file points at :word, which is not a kind of thing a record can point at.', [
                'word' => $this->safe($word),
            ]));
        }

        if (! $type->isRelation()) {
            if ($word !== null) {
                $this->refuse(__('A field in this file says what it points at although it is not a relation.'));
            }

            return null;
        }

        return $word ?? RelationTargetEnum::Contact->value;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>|null
     */
    private function defaultValue(array $field, string $path): ?array
    {
        $default = $field['default_value'] ?? null;

        if ($default === null) {
            return null;
        }

        $values = is_array($default) ? $default : [$default];

        if (! array_is_list($values) || $values === [] || count($values) > self::MAX_DEFAULT_VALUES) {
            $this->refuse(__('A field in this file starts a record with something more complicated than an answer, at :path.', ['path' => $path]));
        }

        foreach ($values as $value) {
            if (is_array($value)) {
                $this->refuse(__('A field in this file starts a record with something more complicated than an answer, at :path.', ['path' => $path]));
            }

            if (is_string($value) && mb_strlen($value) > 65535) {
                $this->refuse(__('A field in this file starts a record with an answer longer than anything can hold, at :path.', ['path' => $path]));
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>|null
     */
    private function options(array $field, string $path): ?array
    {
        if (($field['options'] ?? null) === null) {
            return null;
        }

        $options = StructureFileValues::object($field['options'], "{$path}.options", ['accepted_mime_types'], []);
        $accepted = $options['accepted_mime_types'] ?? null;

        if ($accepted === null) {
            return null;
        }

        if (! is_array($accepted) || ! array_is_list($accepted) || count($accepted) > self::MAX_MIME_TYPES) {
            $this->refuse(__('A field in this file says which kinds of file it takes in a way that cannot be read, at :path.', ['path' => $path]));
        }

        foreach ($accepted as $mime) {
            if (! is_string($mime) || preg_match(self::MIME_TYPE, $mime) !== 1) {
                $this->refuse(__('A field in this file takes a kind of file that is not named as a mime type.'));
            }
        }

        return $accepted === [] ? null : ['accepted_mime_types' => $accepted];
    }

    private function safe(string $word): string
    {
        return StructureFileValues::safe($word);
    }

    private function refuse(string $sentence): never
    {
        StructureFileValues::refuse($sentence);
    }
}
