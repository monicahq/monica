<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\Template;
use App\Models\TemplateVersionPage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use JsonException;

/**
 * Reads a file back, and refuses it when it is not exactly what it claims.
 *
 * This is the only place in the application where text somebody else wrote
 * becomes rows describing what every record renders, so it is written as a
 * doorman rather than as a parser. Nothing is guessed at, nothing is repaired,
 * and nothing is let through on the grounds that it looks harmless: a name this
 * version does not know is a refusal rather than something to ignore, because a
 * file carrying a name we do not know is a file we do not understand. What
 * carries forward compatibility instead is the format version, which is checked
 * first and which is the whole reason the file says what it is.
 *
 * Nothing here touches the database and nothing here creates anything. A
 * refusal therefore means nothing happened, which is what lets a screen offer
 * to check a file as a step of its own.
 *
 * Some of what a file says is read and then deliberately dropped:
 *
 * - Every translation key. An imported structure is never a supplied one, so
 *   the word is resolved once and stored as a sentence. No row of an imported
 *   structure can end up pointing into the catalogue the product ships.
 * - The slug, which is a wish rather than a fact. A slug is an address inside
 *   the account, and {@see SlugBuilder} decides it, so a file cannot take one
 *   that already belongs to something else.
 * - The whole `validation` blob. Nothing reads that column (see
 *   {@see FieldValueRules}), so a file has no business writing it.
 * - Everything in `options` except the accepted mime types, which is the one
 *   part of it anything reads.
 * - A default value on a field that offers choices. What is written down there
 *   is the id of an item in the account it came from, and the file carries no
 *   ids to match it against, so there is nothing here it could mean.
 *
 * The refusals name where in the file the trouble is and do not quote what is
 * in it, so nothing a file says can be made to appear on the screen as though
 * the application were saying it.
 */
class StructureFileReader
{
    /**
     * How much text is worth looking at. A real export of a large template is a
     * few tens of kilobytes, and the ceiling is checked on the bytes before
     * anything tries to parse them.
     */
    public const int MAX_BYTES = 524288;

    /**
     * How deeply a file may nest. The format bottoms out well before this, and
     * the limit is what stops a file built to be expensive to read.
     */
    public const int MAX_DEPTH = 32;

    public const int MAX_PAGES = 25;

    public const int MAX_SECTIONS_PER_PAGE = 50;

    public const int MAX_FIELDS_PER_SECTION = 200;

    public const int MAX_FIELDS = 1000;

    public const int MAX_VALUE_LISTS = 100;

    public const int MAX_ITEMS_PER_LIST = 500;

    public const int MAX_MIME_TYPES = 20;

    public const int MAX_DEFAULT_VALUES = 50;

    /**
     * A key, a slug or a reference: lowercase words joined by a dash or an
     * underscore and nothing else. Every identifier the format carries was
     * built by {@see SlugBuilder} and looks like this.
     */
    private const string IDENTIFIER = '/^[a-z0-9]+([_-][a-z0-9]+)*$/';

    private const string COLOUR = '/^#[0-9a-f]{6}$/';

    private const string LOCALE = '/^[a-z]{2}(_[A-Z]{2})?$/';

    private const string MIME_TYPE = '#^[a-z0-9!\#$&^_.+-]+/[a-z0-9!\#$&^_.+-]+$#';

    /**
     * How many fields the whole file holds, counted while walking it so that a
     * file made of a thousand small sections is refused as surely as one made
     * of a single enormous one.
     */
    private int $fieldCount = 0;

    /**
     * The lists the file declares, by the reference its fields name them by.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $valueLists = [];

    private int $formatVersion = StructureFile::FORMAT_VERSION;

    /**
     * @param  array<string, mixed>  $file
     */
    private function __construct(private readonly array $file) {}

    /**
     * Read a file and hand back what it says, checked.
     *
     * Whoever is reading usually knows which of the two kinds of file they are
     * standing in front of, since the two arrive through different doors. They
     * say so, and a file of the other kind is refused here rather than by each
     * caller in its own words, so that the screen offering to check a file and
     * the action importing it can never disagree about what they would do.
     *
     * @return array<string, mixed>
     *
     * @throws StructureFileIsNotReadable
     */
    public static function read(string $raw, ?string $expecting = null): array
    {
        $read = new self(self::decode($raw))->check();

        if ($expecting === 'template' && $read['format'] !== 'template') {
            throw new StructureFileIsNotReadable(__('This file holds one section rather than a whole template. Open the template you want it on, and import it from the page it should land on.'));
        }

        if ($expecting === 'section' && $read['format'] !== 'section') {
            throw new StructureFileIsNotReadable(__('This file holds a whole template rather than one section. Import it from the list of templates and it arrives as a draft of its own.'));
        }

        return $read;
    }

    /**
     * The text, as far as being text goes: not empty, not enormous, valid
     * UTF-8, valid JSON, and a block rather than a list or a bare word.
     *
     * @return array<string, mixed>
     */
    private static function decode(string $raw): array
    {
        if (mb_trim($raw) === '') {
            throw new StructureFileIsNotReadable(__('There is nothing here to read. Paste the file, outer braces included.'));
        }

        if (strlen($raw) > self::MAX_BYTES) {
            throw new StructureFileIsNotReadable(__('This is larger than a structure file ever is, so it was not read at all. The ceiling is :size.', [
                'size' => Number::fileSize(self::MAX_BYTES),
            ]));
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new StructureFileIsNotReadable(__('This is not text in an encoding that can be read. A structure file is UTF-8.'));
        }

        try {
            $decoded = json_decode($raw, true, self::MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new StructureFileIsNotReadable(__('This is not readable as JSON. Something is missing, repeated or nested deeper than a structure file ever goes.'));
        }

        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new StructureFileIsNotReadable(__('This reads as JSON, but a structure file is a block starting with a brace rather than a list or a single value.'));
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function check(): array
    {
        $format = $this->header();

        $allowed = $format === 'template'
            ? ['monica', 'exported_at', 'template', 'value_lists']
            : ['monica', 'exported_at', 'from_template', 'section', 'value_lists'];

        $file = $this->object($this->file, 'the file', $allowed, ['monica', $format]);

        $this->readTheValueLists();

        return [
            'format' => $format,
            'format_version' => $this->formatVersion,
            'exported_at' => $this->nullableString($file, 'exported_at', 'the file', 64),
            'from_template' => $format === 'section' ? $this->fromTemplate($file) : null,
            'value_lists' => $this->valueLists,
            'template' => $format === 'template' ? $this->template($file['template']) : null,
            'section' => $format === 'section' ? $this->section($file['section'], 'section') : null,
        ];
    }

    /**
     * What the file says it is, which is the first thing read and the only
     * thing a file from a future version is guaranteed to still say the same
     * way.
     */
    private function header(): string
    {
        $node = $this->file['monica'] ?? null;

        if (! is_array($node) || $node === []) {
            $this->refuse(__('There is no Monica header in this file, so there is no saying what it holds. A file exported from Monica starts with `"monica": { "format": … }`.'));
        }

        $header = $this->object($node, 'monica', ['format', 'format_version'], ['format', 'format_version']);

        $format = $this->string($header, 'format', 'monica', 32);

        if ($format !== 'template' && $format !== 'section') {
            $this->refuse(__('The header says :format. Monica reads two kinds of file: a template and a section.', [
                'format' => $this->safe($format),
            ]));
        }

        $this->formatVersion = $this->integer($header, 'format_version', 'monica', 1, 9999);

        if ($this->formatVersion > StructureFile::FORMAT_VERSION) {
            $this->refuse(__('This file was written in format :written, and this account reads format :read. Ask for an export from a Monica of this version, or update yours.', [
                'written' => $this->formatVersion,
                'read' => StructureFile::FORMAT_VERSION,
            ]));
        }

        return $format;
    }

    /**
     * Where a section file was cut from. It is provenance and nothing else:
     * nothing here is resolved against anything, and nothing is written down
     * from it.
     *
     * @param  array<string, mixed>  $file
     * @return array<string, mixed>|null
     */
    private function fromTemplate(array $file): ?array
    {
        if (($file['from_template'] ?? null) === null) {
            return null;
        }

        $from = $this->object($file['from_template'], 'from_template', ['name', 'version'], []);

        return [
            'name' => $this->nullableString($from, 'name', 'from_template', 255),
            'version' => $this->nullableInteger($from, 'version', 'from_template', 1, 65535),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function template(mixed $node): array
    {
        $template = $this->object($node, 'template', [
            'slug', 'name', 'name_translation_key', 'description', 'applies_to', 'locale',
            'version_number', 'entity_type', 'pages',
        ], ['pages']);

        $appliesTo = $this->string($template, 'applies_to', 'template', 32, TemplateAppliesToEnum::Contact->value);

        if (TemplateAppliesToEnum::tryFrom($appliesTo) === null) {
            $this->refuse(__('The template says it applies to :word, which is not something this version knows about.', [
                'word' => $this->safe($appliesTo),
            ]));
        }

        $entityType = $this->entityType($template['entity_type'] ?? null, 'template.entity_type');

        if ($appliesTo === TemplateAppliesToEnum::Entity->value && $entityType === null) {
            $this->refuse(__('The template says it describes a kind of record, but it does not say which one.'));
        }

        $pages = $this->pages($template['pages']);

        if ($pages === []) {
            $this->refuse(__('The header is right, but the template holds no page, so there is nothing in it to build.'));
        }

        return [
            'slug' => $this->identifier($template, 'slug', 'template', Template::SLUG_LENGTH),
            'name' => $this->word($template, 'name', 'name_translation_key', 'template', 255),
            'description' => $this->nullableString($template, 'description', 'template', 65535),
            'applies_to' => $appliesTo,
            'locale' => $this->locale($template, 'template'),
            'entity_type' => $appliesTo === TemplateAppliesToEnum::Entity->value ? $entityType : null,
            'pages' => $pages,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pages(mixed $node): array
    {
        $rows = $this->collection($node, 'template.pages', self::MAX_PAGES);
        $keys = [];
        $pages = [];

        foreach ($rows as $index => $row) {
            $path = "template.pages.{$index}";

            $page = $this->object($row, $path, ['key', 'label', 'label_translation_key', 'position', 'sections'], ['sections']);
            $label = $this->word($page, 'label', 'label_translation_key', $path, 255);
            $key = $this->identifier($page, 'key', $path, TemplateVersionPage::KEY_LENGTH) ?? Str::slug($label);
            $key = $key === '' ? 'page' : $key;

            if (in_array($key, $keys, true)) {
                $this->refuse(__('Two pages of this template carry the same key, so one of them would take the place of the other.'));
            }

            $keys[] = $key;

            $pages[] = [
                'key' => $key,
                'label' => $label,
                'position' => $this->integer($page, 'position', $path, 0, 65535, $index + 1),
                'sections' => $this->assembly($page['sections'], $path),
            ];
        }

        return $pages;
    }

    /**
     * The sections a page carries, each with what the page says about how it
     * sits there rather than about what it is.
     *
     * @return list<array<string, mixed>>
     */
    private function assembly(mixed $node, string $parentPath): array
    {
        $rows = $this->collection($node, "{$parentPath}.sections", self::MAX_SECTIONS_PER_PAGE);
        $assembly = [];

        foreach ($rows as $index => $row) {
            $path = "{$parentPath}.sections.{$index}";

            $placement = $this->object($row, $path, ['position', 'title_override', 'is_collapsed_by_default', 'section'], ['section']);

            $assembly[] = [
                'position' => $this->integer($placement, 'position', $path, 0, 65535, $index + 1),
                'title_override' => $this->nullableString($placement, 'title_override', $path, 255),
                'is_collapsed_by_default' => $this->boolean($placement, 'is_collapsed_by_default', $path),
                'section' => $this->section($placement['section'], "{$path}.section"),
            ];
        }

        return $assembly;
    }

    /**
     * @return array<string, mixed>
     */
    private function section(mixed $node, string $path): array
    {
        $section = $this->object($node, $path, [
            'slug', 'name', 'name_translation_key', 'description', 'icon', 'locale',
            'version_number', 'fields',
        ], ['fields']);

        $fields = $this->fields($section['fields'], $path);

        if ($fields === []) {
            $this->refuse(__('The header is right, but a section in this file holds no field, so there is nothing in it to build.'));
        }

        return [
            'slug' => $this->identifier($section, 'slug', $path, Section::SLUG_LENGTH),
            'name' => $this->word($section, 'name', 'name_translation_key', $path, 255),
            'description' => $this->nullableString($section, 'description', $path, 65535),
            'icon' => $this->identifier($section, 'icon', $path, 64),
            'locale' => $this->locale($section, $path),
            'fields' => $fields,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fields(mixed $node, string $parentPath): array
    {
        $rows = $this->collection($node, "{$parentPath}.fields", self::MAX_FIELDS_PER_SECTION);

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

    /**
     * @return array<string, mixed>
     */
    private function field(mixed $node, string $path, int $index): array
    {
        $field = $this->object($node, $path, [
            'key', 'type', 'label', 'label_translation_key', 'help_text', 'placeholder', 'position',
            'is_required', 'is_sensitive', 'is_highlighted', 'is_unique', 'is_repeatable',
            'repeat_min', 'repeat_max', 'is_reminder_source', 'default_value', 'options', 'validation',
            'parent_field_key', 'value_list_reference', 'relation_target', 'relation_entity_type',
        ], ['type']);

        $word = $this->string($field, 'type', $path, 32);
        $type = FieldTypeEnum::tryFrom($word);

        if ($type === null) {
            $this->refuse(__('A field in this file is of the kind :word, which this version does not know how to render.', [
                'word' => $this->safe($word),
            ]));
        }

        $label = $this->word($field, 'label', 'label_translation_key', $path, 255);
        $key = $this->identifier($field, 'key', $path, Field::KEY_LENGTH) ?? Str::slug($label, '_');
        $reference = $this->identifier($field, 'value_list_reference', $path, 64);

        if ($reference !== null && ! isset($this->valueLists[$reference])) {
            $this->refuse(__('A field in this file offers choices from a list the file does not carry.'));
        }

        if ($type->usesValueList() && $reference === null) {
            $this->refuse(__('A field in this file offers choices without saying which list they come from.'));
        }

        if (! $type->usesValueList() && $reference !== null) {
            $this->refuse(__('A field in this file names a list of choices although its kind offers none.'));
        }

        $relationTarget = $this->relationTarget($field, $path, $type);
        $relationEntityType = $this->entityType($field['relation_entity_type'] ?? null, "{$path}.relation_entity_type");

        if (! $type->isRelation() && $relationEntityType !== null) {
            $this->refuse(__('A field in this file points at a kind of record although it is not a relation.'));
        }

        if ($relationTarget === RelationTargetEnum::Entity->value && $relationEntityType === null) {
            $this->refuse(__('A relation in this file points at a kind of record without saying which one.'));
        }

        $isRepeatable = $this->boolean($field, 'is_repeatable', $path, $type->isRepeater());

        if ($isRepeatable !== $type->isRepeater()) {
            $this->refuse(__('A field in this file says it repeats although its kind does not, or the other way round.'));
        }

        $repeatMin = $type->isRepeater() ? $this->nullableInteger($field, 'repeat_min', $path, 0, 100) : null;
        $repeatMax = $type->isRepeater() ? $this->nullableInteger($field, 'repeat_max', $path, 0, 100) : null;

        if ($repeatMin !== null && $repeatMax !== null && $repeatMin > $repeatMax) {
            $this->refuse(__('A repeater in this file asks for more repetitions at least than it allows at most.'));
        }

        $isReminderSource = $this->boolean($field, 'is_reminder_source', $path);

        if ($isReminderSource && ! $type->canDriveAReminder()) {
            $this->refuse(__('A field in this file says it drives a reminder although its kind cannot.'));
        }

        return [
            'key' => $key === '' ? 'field' : $key,
            'type' => $type->value,
            'label' => $label,
            'help_text' => $this->nullableString($field, 'help_text', $path, 255),
            'placeholder' => $this->nullableString($field, 'placeholder', $path, 255),
            'position' => $this->integer($field, 'position', $path, 0, 65535, $index + 1),
            'is_required' => $this->boolean($field, 'is_required', $path),
            'is_sensitive' => $this->boolean($field, 'is_sensitive', $path),
            'is_highlighted' => $this->boolean($field, 'is_highlighted', $path),
            'is_unique' => $this->boolean($field, 'is_unique', $path),
            'is_repeatable' => $isRepeatable,
            'repeat_min' => $repeatMin,
            'repeat_max' => $repeatMax,
            'is_reminder_source' => $isReminderSource,
            'default_value' => $type->usesValueList() ? null : $this->defaultValue($field, $path),
            'options' => $this->options($field, $path),
            'parent_field_key' => $this->identifier($field, 'parent_field_key', $path, Field::KEY_LENGTH),
            'value_list_reference' => $reference,
            'relation_target' => $relationTarget,
            'relation_entity_type' => $type->isRelation() ? $relationEntityType : null,
        ];
    }

    /**
     * Which repeater each field sits in, checked once the whole section is
     * known, since a field may name one written after it in the file.
     *
     * @param  list<array<string, mixed>>  $fields
     */
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

    /**
     * The lists of choices the file carries, named by the reference the fields
     * point at rather than by any number, since a number out of another account
     * means nothing here.
     */
    private function readTheValueLists(): void
    {
        $node = $this->file['value_lists'] ?? [];

        if (! is_array($node)) {
            $this->refuse(__('The lists of choices in this file are not written as a block.'));
        }

        if (count($node) > self::MAX_VALUE_LISTS) {
            $this->refuse(trans_choice(
                'This file carries more than :count list of choices.|This file carries more than :count lists of choices.',
                self::MAX_VALUE_LISTS,
            ));
        }

        foreach ($node as $name => $row) {
            $reference = (string) $name;

            if (mb_strlen($reference) > 64 || preg_match(self::IDENTIFIER, $reference) !== 1) {
                $this->refuse(__('A list of choices in this file is named in a way that is not a reference.'));
            }

            $path = "value_lists.{$reference}";
            $list = $this->object($row, $path, ['key', 'name', 'name_translation_key', 'items'], ['items']);

            $this->valueLists[$reference] = [
                'key' => $this->identifier($list, 'key', $path, 64),
                'name' => $this->word($list, 'name', 'name_translation_key', $path, 255),
                'items' => $this->valueListItems($list['items'], $path),
            ];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function valueListItems(mixed $node, string $parentPath): array
    {
        $rows = $this->collection($node, "{$parentPath}.items", self::MAX_ITEMS_PER_LIST);
        $items = [];
        $keys = [];

        foreach ($rows as $index => $row) {
            $path = "{$parentPath}.items.{$index}";

            $item = $this->object($row, $path, ['key', 'label', 'label_translation_key', 'position', 'is_active'], []);
            $key = $this->identifier($item, 'key', $path, 64);

            if ($key !== null && in_array($key, $keys, true)) {
                $this->refuse(__('Two choices of one list carry the same key.'));
            }

            if ($key !== null) {
                $keys[] = $key;
            }

            $items[] = [
                'key' => $key,
                'label' => $this->word($item, 'label', 'label_translation_key', $path, 255),
                'position' => $this->integer($item, 'position', $path, 0, 65535, $index + 1),
                'is_active' => $this->boolean($item, 'is_active', $path, true),
            ];
        }

        if ($items === []) {
            $this->refuse(__('A list of choices in this file holds nothing to choose from.'));
        }

        return $items;
    }

    /**
     * A kind of record, described rather than pointed at, since one invented in
     * an account exists nowhere else.
     *
     * @return array<string, mixed>|null
     */
    private function entityType(mixed $node, string $path): ?array
    {
        if ($node === null) {
            return null;
        }

        $entityType = $this->object($node, $path, ['slug', 'name_singular', 'name_plural', 'description', 'icon', 'colour'], ['name_singular', 'name_plural']);

        $icon = $this->identifier($entityType, 'icon', $path, 64);

        if ($icon !== null && ! in_array($icon, EntityType::ICONS, true)) {
            $this->refuse(__('A kind of record in this file asks for a glyph this version does not have.'));
        }

        $colour = $this->nullableString($entityType, 'colour', $path, 7);

        if ($colour !== null && preg_match(self::COLOUR, mb_strtolower($colour)) !== 1) {
            $this->refuse(__('A kind of record in this file asks for a colour that is not written as a hexadecimal one.'));
        }

        $nameSingular = $this->string($entityType, 'name_singular', $path, 255);
        $slug = $this->identifier($entityType, 'slug', $path, EntityType::SLUG_LENGTH) ?? Str::slug($nameSingular);

        return [
            'slug' => $slug === '' ? 'kind' : $slug,
            'name_singular' => $nameSingular,
            'name_plural' => $this->string($entityType, 'name_plural', $path, 255),
            'description' => $this->nullableString($entityType, 'description', $path, 65535),
            'icon' => $icon,
            'colour' => $colour === null ? null : mb_strtolower($colour),
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function relationTarget(array $field, string $path, FieldTypeEnum $type): ?string
    {
        $word = $this->nullableString($field, 'relation_target', $path, 32);

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
     * What a record starts with in this field. It is stored as free json, so
     * what is allowed is one answer or a flat list of them, and never a shape
     * of its own.
     *
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
     * What a field says about itself beyond its kind. Only the accepted mime
     * types are read, because they are the only part of it anything reads.
     *
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>|null
     */
    private function options(array $field, string $path): ?array
    {
        if (($field['options'] ?? null) === null) {
            return null;
        }

        $options = $this->object($field['options'], "{$path}.options", ['accepted_mime_types'], []);
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

    /**
     * A block, holding what it is allowed to hold and nothing else.
     *
     * @param  list<string>  $allowed
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function object(mixed $node, string $path, array $allowed, array $required): array
    {
        if (! is_array($node)) {
            $this->refuse(__('The file holds something other than a block at :path.', ['path' => $path]));
        }

        foreach (array_keys($node) as $key) {
            if (! in_array((string) $key, $allowed, true)) {
                $this->refuse(__('The file holds a name this version does not know at :path, so what it means cannot be guessed at.', [
                    'path' => "{$path}.{$this->safe((string) $key)}",
                ]));
            }
        }

        foreach ($required as $key) {
            if (($node[$key] ?? null) === null) {
                $this->refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
            }
        }

        return $node;
    }

    /**
     * A list of blocks, of a length worth reading.
     *
     * @return list<mixed>
     */
    private function collection(mixed $node, string $path, int $ceiling): array
    {
        if (! is_array($node) || ! array_is_list($node)) {
            $this->refuse(__('The file holds something other than a list at :path.', ['path' => $path]));
        }

        if (count($node) > $ceiling) {
            $this->refuse(trans_choice(
                'The file holds more than :count thing at :path.|The file holds more than :count things at :path.',
                $ceiling,
                ['path' => $path],
            ));
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function string(array $node, string $key, string $path, int $max, ?string $whenMissing = null): string
    {
        $value = $this->nullableString($node, $key, $path, $max);

        if ($value !== null) {
            return $value;
        }

        if ($whenMissing !== null) {
            return $whenMissing;
        }

        $this->refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function nullableString(array $node, string $key, string $path, int $max): ?string
    {
        $value = $node[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            $this->refuse(__('The file holds something other than words at :path.', ['path' => "{$path}.{$key}"]));
        }

        $value = mb_trim($value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $max) {
            $this->refuse(trans_choice(
                'The file holds more than :count character at :path, which is more than can be stored.|The file holds more than :count characters at :path, which is more than can be stored.',
                $max,
                ['path' => "{$path}.{$key}"],
            ));
        }

        return $value;
    }

    /**
     * A key, a slug or a reference, which is a word rather than a sentence.
     *
     * @param  array<string, mixed>  $node
     */
    private function identifier(array $node, string $key, string $path, int $max): ?string
    {
        $value = $this->nullableString($node, $key, $path, $max);

        if ($value === null) {
            return null;
        }

        if (preg_match(self::IDENTIFIER, $value) !== 1) {
            $this->refuse(__('The file holds something other than a key at :path. A key is lowercase letters and numbers, joined by dashes or underscores.', [
                'path' => "{$path}.{$key}",
            ]));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function integer(array $node, string $key, string $path, int $min, int $max, ?int $whenMissing = null): int
    {
        $value = $this->nullableInteger($node, $key, $path, $min, $max);

        if ($value !== null) {
            return $value;
        }

        if ($whenMissing !== null) {
            return $whenMissing;
        }

        $this->refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function nullableInteger(array $node, string $key, string $path, int $min, int $max): ?int
    {
        $value = $node[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_int($value) || $value < $min || $value > $max) {
            $this->refuse(__('The file holds something other than a whole number between :min and :max at :path.', [
                'min' => $min,
                'max' => $max,
                'path' => "{$path}.{$key}",
            ]));
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function boolean(array $node, string $key, string $path, bool $whenMissing = false): bool
    {
        $value = $node[$key] ?? null;

        if ($value === null) {
            return $whenMissing;
        }

        if (! is_bool($value)) {
            $this->refuse(__('The file holds something other than a yes or a no at :path.', ['path' => "{$path}.{$key}"]));
        }

        return $value;
    }

    /**
     * The language the structure was written in. One this account has never
     * heard of is dropped rather than refused: it says nothing about what the
     * structure is, and the account it lands in has a language of its own.
     *
     * @param  array<string, mixed>  $node
     */
    private function locale(array $node, string $path): ?string
    {
        $locale = $this->nullableString($node, 'locale', $path, 5);

        if ($locale === null || preg_match(self::LOCALE, $locale) !== 1) {
            return null;
        }

        return array_key_exists($locale, (array) config('monica.locales')) ? $locale : null;
    }

    /**
     * The word a thing is called, whether the file wrote it down or named a
     * translation of it.
     *
     * A file naming a translation describes a structure the product supplied
     * somewhere else. What comes in is never supplied here, so the translation
     * is read once, now, and what is kept is the sentence rather than the key.
     *
     * @param  array<string, mixed>  $node
     */
    private function word(array $node, string $key, string $translationKey, string $path, int $max): string
    {
        $literal = $this->nullableString($node, $key, $path, $max);

        if ($literal !== null) {
            return $literal;
        }

        $reference = $this->nullableString($node, $translationKey, $path, $max);

        if ($reference === null) {
            $this->refuse(__('The file says nothing about :path, which it has to.', ['path' => "{$path}.{$key}"]));
        }

        $translated = __($reference);

        if (! is_string($translated) || mb_trim($translated) === '') {
            return $reference;
        }

        return Str::limit(mb_trim($translated), $max, '');
    }

    /**
     * A word out of the file, made safe to put in a sentence on the screen.
     * Anything that is not already a plain short word is left out entirely,
     * since a refusal reads perfectly well without it.
     */
    private function safe(string $word): string
    {
        return preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $word) === 1 ? $word : '…';
    }

    private function refuse(string $sentence): never
    {
        throw new StructureFileIsNotReadable($sentence);
    }
}
