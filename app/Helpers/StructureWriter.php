<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Actions\CreateEntityType;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\VersionStatusEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;

/**
 * Builds what a checked file describes, inside an account.
 *
 * The exact mirror of {@see MarketplacePayload}, which reads an account into a
 * payload; this reads a payload into an account. It is a class of its own
 * rather than a method on either import action because a template file and a
 * section file describe a section the same way, and two copies of that walk
 * would be two chances for the two doors to build different things out of the
 * same bytes.
 *
 * It writes rows rather than calling the `Create` actions. Those recompute
 * every position, refuse to write a value list key, and would log once per
 * field, which for a file holding two hundred of them is two hundred lines
 * saying nothing anybody wanted to read. The import is one thing somebody did,
 * and the action that owns the transaction logs it once.
 *
 * Nothing here is reached without {@see StructureFileReader} having read the
 * file first. Every string it hands over has already been checked for length,
 * shape and vocabulary, and is cleaned again on the way into the row, the way
 * everything written on somebody's say so is.
 *
 * What comes in is never a supplied structure. Nothing here writes
 * `is_system`, and nothing writes a translation key: an imported template is an
 * ordinary template of the account from its first moment, which is also what
 * makes it editable.
 */
class StructureWriter
{
    /**
     * The list each reference in the file resolved to here, whether it was
     * found or made.
     *
     * @var array<string, ValueList>
     */
    private array $valueLists = [];

    /**
     * The kinds of record named by the file, by slug, so a template naming the
     * same one from its own row and from three relations makes it once.
     *
     * @var array<string, EntityType>
     */
    private array $entityTypes = [];

    /**
     * @param  array<string, mixed>  $file
     */
    public function __construct(
        private readonly User $user,
        private readonly array $file,
    ) {}

    /**
     * Settle every list of choices the file points at, before anything points
     * at one.
     *
     * A list the product supplies has the same key in every account, and that
     * is what lets a file recognise the "Countries" this account already
     * curates rather than leaving somebody with a third copy of it. A list
     * somebody invented has no key, so it is always copied: there is nothing to
     * recognise it by, and guessing from the name would silently merge two
     * lists that happen to share one.
     */
    public function settleTheValueLists(): void
    {
        foreach ($this->file['value_lists'] as $reference => $list) {
            $this->valueLists[$reference] = $this->valueList($list);
        }
    }

    /**
     * Build one section, frozen.
     *
     * It arrives published rather than as a draft because it arrived frozen: it
     * is a version somebody else finished, not a version being argued with
     * here. Publishing it is also what takes the snapshot of the lists its
     * fields ended up pointing at, which is what an export of it later reads.
     *
     * The fields are written while the version is still a draft, since a
     * published one refuses them, and parents before children, since a field
     * names the repeater it sits in by key.
     *
     * @param  array<string, mixed>  $payload
     */
    public function section(array $payload): SectionVersion
    {
        $name = TextSanitizer::plainText($payload['name']);

        $section = Section::query()->create([
            'account_id' => $this->user->account_id,
            'name' => $name,
            'name_translation_key' => null,
            'description' => TextSanitizer::nullablePlainText($payload['description']),
            'slug' => SlugBuilder::build(
                wanted: $payload['slug'],
                derivedFrom: $name,
                maxLength: Section::SLUG_LENGTH,
                whenEmpty: 'section',
                isTaken: fn (string $slug): bool => Section::query()
                    ->withTrashed()
                    ->where('account_id', $this->user->account_id)
                    ->where('slug', $slug)
                    ->exists(),
            ),
            'icon' => TextSanitizer::nullablePlainText($payload['icon']),
            'is_system' => false,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => $payload['locale'] ?? $this->user->locale ?? config('app.locale'),
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

        $this->fields($version, $payload['fields']);

        $version->status = VersionStatusEnum::Published;
        $version->resolved_value_lists = ValueListSnapshot::of($version);
        $version->published_at = now();
        $version->save();

        $section->published_version_id = $version->id;
        $section->save();

        return $version;
    }

    /**
     * The kind of record the file describes, found here by slug or declared.
     *
     * A relation pointing at a kind of record this account has never heard of
     * would be a field nobody can fill in, so the kind is declared rather than
     * the field dropped. It goes through the action, since a new kind of record
     * is an ordinary thing somebody did and belongs in the log beside the
     * import that caused it.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function entityType(?array $payload): ?EntityType
    {
        if ($payload === null) {
            return null;
        }

        $slug = $payload['slug'];

        if (isset($this->entityTypes[$slug])) {
            return $this->entityTypes[$slug];
        }

        $existing = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->where('slug', $slug)
            ->first();

        if ($existing !== null) {
            return $this->entityTypes[$slug] = $existing;
        }

        return $this->entityTypes[$slug] = new CreateEntityType(
            user: $this->user,
            nameSingular: $payload['name_singular'],
            namePlural: $payload['name_plural'],
            description: $payload['description'],
            icon: $payload['icon'],
            colour: $payload['colour'],
            slug: $slug,
        )->execute();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function valueList(array $payload): ValueList
    {
        $key = $payload['key'];

        if ($key !== null) {
            $mine = ValueList::query()
                ->where('account_id', $this->user->account_id)
                ->where('key', $key)
                ->first();

            if ($mine !== null) {
                return $mine;
            }
        }

        /* A deleted list keeps its key, so a file naming one that was thrown
           away here is copied under no key rather than resurrecting it. */
        if ($key !== null && ValueList::query()->withTrashed()->where('account_id', $this->user->account_id)->where('key', $key)->exists()) {
            $key = null;
        }

        $positions = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $list = ValueList::query()->create([
            'account_id' => $this->user->account_id,
            'key' => $key,
            'name' => TextSanitizer::plainText($payload['name']),
            'name_translation_key' => null,
            'description' => null,
            'is_system' => false,
            'allows_user_values' => true,
            'position' => ($positions->max() ?? 0) + 1,
        ]);

        foreach ($payload['items'] as $item) {
            ValueListItem::query()->create([
                'account_id' => $this->user->account_id,
                'value_list_id' => $list->id,
                'key' => $item['key'],
                'label' => TextSanitizer::plainText($item['label']),
                'label_translation_key' => null,
                'description' => null,
                'colour' => null,
                'icon' => null,
                'position' => $item['position'],
                'is_system' => false,
                'is_active' => $item['is_active'],
                'usage_count' => 0,
            ]);
        }

        return $list;
    }

    /**
     * @param  list<array<string, mixed>>  $payload
     */
    private function fields(SectionVersion $version, array $payload): void
    {
        $parents = [];

        foreach ($payload as $field) {
            if ($field['parent_field_key'] === null) {
                $parents[$field['key']] = $this->field($version, $field, null);
            }
        }

        foreach ($payload as $field) {
            if ($field['parent_field_key'] !== null) {
                $this->field($version, $field, $parents[$field['parent_field_key']]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function field(SectionVersion $version, array $payload, ?Field $parent): Field
    {
        $type = FieldTypeEnum::from($payload['type']);
        $reference = $payload['value_list_reference'];

        return Field::query()->create([
            'account_id' => $this->user->account_id,
            'section_version_id' => $version->id,
            'type' => $type,
            'key' => $payload['key'],
            'label' => TextSanitizer::plainText($payload['label']),
            'label_translation_key' => null,
            'help_text' => TextSanitizer::nullablePlainText($payload['help_text']),
            'placeholder' => TextSanitizer::nullablePlainText($payload['placeholder']),
            'position' => $payload['position'],
            'is_required' => $payload['is_required'],
            'is_sensitive' => $payload['is_sensitive'],
            'is_highlighted' => $payload['is_highlighted'],
            'is_unique' => $payload['is_unique'],
            'value_list_id' => $reference === null ? null : $this->valueLists[$reference]->id,
            'default_value' => $payload['default_value'],
            'options' => $payload['options'],
            'validation' => null,
            'is_repeatable' => $payload['is_repeatable'],
            'repeat_min' => $payload['repeat_min'],
            'repeat_max' => $payload['repeat_max'],
            'parent_field_id' => $parent?->id,
            'relation_target' => $payload['relation_target'] === null ? null : RelationTargetEnum::from($payload['relation_target']),
            'relation_entity_type_id' => $this->entityType($payload['relation_entity_type'])?->id,
        ]);
    }
}
