<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\StructureFile;
use App\Models\AccountExport;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\ValueListItem;
use App\Models\Vault;
use Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Write every record of the vaults an export covers, with the structures and the types they use and
 * the bytes of the files they point at, into one JSON document at the given path on the export's disk.
 */
class WriteAccountExport
{
    public const string VERSION = '2.0-preview.1';

    private const int CHUNK = 200;

    /** @var resource */
    private $stream;

    /** @var array<int, array{template: string, version_number: int}> */
    private array $structureReferences = [];

    /** @var array<int, string> */
    private array $relationshipTypeNames = [];

    /** @var array<int, string> */
    private array $lifeEventTypeNames = [];

    /** @var array<int, array{life_event_type: string, version_number: int}> */
    private array $lifeEventTypeVersions = [];

    /** @var array<int, string> */
    private array $itemNames = [];

    /** @var array<int, Field> */
    private array $fields = [];

    /** @var array<int, string> */
    private array $contactUuids = [];

    /** @var array<int, string> */
    private array $entityUuids = [];

    /** @var array<int, string> */
    private array $lifeEventUuids = [];

    /** @var array<int, true> */
    private array $fileIds = [];

    public function __construct(
        private readonly AccountExport $export,
        private readonly string $path,
    ) {}

    public function execute(): void
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('No temporary stream could be opened to write the export into.');
        }

        $this->stream = $stream;

        try {
            $this->writeDocument();
            $this->store();
        } finally {
            fclose($this->stream);
        }
    }

    private function writeDocument(): void
    {
        $vaults = Vault::query()
            ->where('account_id', $this->export->account_id)
            ->whereIn('id', $this->export->vault_ids)
            ->orderBy('position')
            ->get();

        $vaultIds = $vaults->modelKeys();

        $this->itemNames = ValueListItem::withTrashed()
            ->where('account_id', $this->export->account_id)
            ->get()
            ->mapWithKeys(fn (ValueListItem $item): array => [$item->id => $item->key ?? (string) $item->getRawOriginal('label')])
            ->all();

        $this->put(substr($this->encode([
            'version' => self::VERSION,
            'app_version' => (string) config('monica.version'),
            'exported_at' => now()->toIso8601ZuluString(),
        ]), 0, -1).',"account":{');

        $this->putList('structures', $this->structures($vaultIds));
        $this->put(',');
        $this->putList('life_event_types', $this->lifeEventTypes($vaultIds));
        $this->put(',');
        $this->putList('relationship_types', $this->relationshipTypes($vaultIds));
        $this->put(',"vaults":[');

        foreach ($vaults->values() as $index => $vault) {
            $this->put($index === 0 ? '' : ',');
            $this->putVault($vault);
        }

        $this->put(']}}');
    }

    private function store(): void
    {
        rewind($this->stream);

        if (! Storage::disk($this->export->disk)->writeStream($this->path, $this->stream)) {
            throw new RuntimeException('The export could not be written to its disk.');
        }
    }

    /**
     * @param  array<int, int>  $vaultIds
     * @return Generator<int, array<string, mixed>>
     */
    private function structures(array $vaultIds): Generator
    {
        $versionIds = Contact::query()->whereIn('vault_id', $vaultIds)->distinct()->pluck('structure_version_id')
            ->merge(Entity::query()->whereIn('vault_id', $vaultIds)->distinct()->pluck('structure_version_id'))
            ->unique()
            ->all();

        $versions = StructureVersion::query()
            ->with('structure.owner')
            ->whereIn('id', $versionIds)
            ->orderBy('id')
            ->get();

        foreach ($versions as $version) {
            $template = $version->structure->owner;

            if (! $template instanceof Template) {
                continue;
            }

            $this->structureReferences[$version->id] = ['template' => $template->uuid, 'version_number' => $version->version_number];

            yield [
                'template' => $template->uuid,
                'version_number' => $version->version_number,
                'document' => StructureFile::ofTemplateVersion($template, $version),
            ];
        }
    }

    /**
     * @param  array<int, int>  $vaultIds
     * @return Generator<int, array<string, mixed>>
     */
    private function lifeEventTypes(array $vaultIds): Generator
    {
        $types = LifeEventType::withTrashed()
            ->whereIn('id', LifeEvent::query()->whereIn('vault_id', $vaultIds)->select('life_event_type_id'))
            ->with('lifeEventCategory')
            ->get()
            ->keyBy('id');

        foreach ($types as $type) {
            $this->lifeEventTypeNames[$type->id] = $type->key ?? $type->uuid;
        }

        $versions = StructureVersion::query()
            ->with('structure')
            ->whereIn('id', LifeEvent::query()->whereIn('vault_id', $vaultIds)->select('structure_version_id'))
            ->orderBy('id')
            ->get();

        foreach ($versions as $version) {
            $type = $version->structure->owner_type === new LifeEventType()->getMorphClass()
                ? $types->get($version->structure->owner_id)
                : null;

            if (! $type instanceof LifeEventType) {
                continue;
            }

            $this->lifeEventTypeVersions[$version->id] = ['life_event_type' => $type->key ?? $type->uuid, 'version_number' => $version->version_number];

            yield [
                'uuid' => $type->uuid,
                'key' => $type->key,
                'version_number' => $version->version_number,
                'document' => StructureFile::ofLifeEventTypeVersion($type, $version),
            ];
        }
    }

    /**
     * @param  array<int, int>  $vaultIds
     * @return Generator<int, array<string, mixed>>
     */
    private function relationshipTypes(array $vaultIds): Generator
    {
        $types = RelationshipType::withTrashed()
            ->whereIn('id', Relationship::query()->whereIn('vault_id', $vaultIds)->select('relationship_type_id'))
            ->with('relationshipCategory')
            ->orderBy('id')
            ->get();

        foreach ($types as $type) {
            $this->relationshipTypeNames[$type->id] = $type->key ?? $type->uuid;

            if ($type->key !== null) {
                continue;
            }

            yield [
                'uuid' => $type->uuid,
                'key' => null,
                'category' => $type->relationshipCategory->key ?? $type->relationshipCategory->name,
                'name' => $type->name,
                'role_a' => $type->role_a,
                'role_b' => $type->role_b,
                'is_symmetric' => $type->is_symmetric,
            ];
        }
    }

    private function putVault(Vault $vault): void
    {
        $this->contactUuids = Contact::query()->where('vault_id', $vault->id)->pluck('uuid', 'id')->all();
        $this->entityUuids = Entity::query()->where('vault_id', $vault->id)->pluck('uuid', 'id')->all();
        $this->lifeEventUuids = LifeEvent::query()->where('vault_id', $vault->id)->pluck('uuid', 'id')->all();
        $this->fileIds = [];

        $this->put(substr($this->encode([
            'uuid' => $vault->uuid,
            'name' => $vault->name,
            'description' => $vault->description,
            'colour' => $vault->colour,
        ]), 0, -1).',');

        $this->putList('contacts', $this->contacts($vault));
        $this->put(',');
        $this->putList('entities', $this->entities($vault));
        $this->put(',');
        $this->putList('relationships', $this->relationships($vault));
        $this->put(',');
        $this->putList('groups', $this->groups($vault));
        $this->put(',');
        $this->putList('life_events', $this->lifeEvents($vault));
        $this->put(',');
        $this->putList('reminders', $this->reminders($vault));
        $this->put(',"files":[');
        $this->putFiles();
        $this->put(']}');
    }

    /** @return Generator<int, array<string, mixed>> */
    private function contacts(Vault $vault): Generator
    {
        $contacts = Contact::query()
            ->where('vault_id', $vault->id)
            ->with(['nameRepresentations', 'avatar', 'fieldValues' => fn ($query) => $query->attached()->with(['row', 'file'])])
            ->lazyById(self::CHUNK);

        foreach ($contacts as $contact) {
            yield [
                'uuid' => $contact->uuid,
                'created_at' => $contact->created_at->toIso8601ZuluString(),
                'is_me' => $contact->is_me,
                'is_partial' => $contact->is_partial,
                'is_favourite' => $contact->is_favourite,
                'is_archived' => $contact->is_archived,
                'nickname' => $contact->nickname,
                'maiden_name' => $contact->maiden_name,
                'name_representations' => $contact->nameRepresentations
                    ->map(fn (ContactNameRepresentation $name): array => [
                        'is_display' => $name->id === $contact->display_name_representation_id,
                        'kind' => $name->kind->value,
                        'language_tag' => $name->language_tag,
                        'script_code' => $name->script_code,
                        'phonetic_system' => $name->phonetic_system,
                        'formatted_name' => $name->formatted_name,
                        'prefix' => $name->prefix,
                        'given_name' => $name->given_name,
                        'additional_name' => $name->additional_name,
                        'family_name' => $name->family_name,
                        'suffix' => $name->suffix,
                        'name_order' => $name->name_order?->value,
                    ])
                    ->values()
                    ->all(),
                'structure' => $this->structureReferences[$contact->structure_version_id] ?? null,
                'avatar_file' => $this->fileReference($contact->avatar),
                'values' => $this->values($contact->fieldValues),
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function entities(Vault $vault): Generator
    {
        $entities = Entity::query()
            ->where('vault_id', $vault->id)
            ->with(['entityType', 'avatar', 'fieldValues' => fn ($query) => $query->attached()->with(['row', 'file'])])
            ->lazyById(self::CHUNK);

        foreach ($entities as $entity) {
            yield [
                'uuid' => $entity->uuid,
                'created_at' => $entity->created_at->toIso8601ZuluString(),
                'entity_type' => $entity->entityType->slug,
                'name' => $entity->name,
                'is_archived' => $entity->is_archived,
                'structure' => $this->structureReferences[$entity->structure_version_id] ?? null,
                'avatar_file' => $this->fileReference($entity->avatar),
                'values' => $this->values($entity->fieldValues),
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function relationships(Vault $vault): Generator
    {
        $relationships = Relationship::query()->where('vault_id', $vault->id)->lazyById(self::CHUNK);

        foreach ($relationships as $relationship) {
            $contact = $this->contactUuids[$relationship->contact_a_id] ?? null;
            $otherContact = $this->contactUuids[$relationship->contact_b_id] ?? null;

            if ($contact === null) {
                continue;
            }

            if ($otherContact === null) {
                continue;
            }

            yield [
                'uuid' => $relationship->uuid,
                'created_at' => $relationship->created_at->toIso8601ZuluString(),
                'type' => $this->relationshipTypeNames[$relationship->relationship_type_id] ?? null,
                'contact' => $contact,
                'other_contact' => $otherContact,
                'status' => $relationship->status->value,
                'started_on' => $relationship->started_on?->format('Y-m-d'),
                'ended_on' => $relationship->ended_on?->format('Y-m-d'),
                'notes' => $relationship->notes,
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function groups(Vault $vault): Generator
    {
        $groups = Group::query()->where('vault_id', $vault->id)->with('members')->lazyById(self::CHUNK);

        foreach ($groups as $group) {
            yield [
                'uuid' => $group->uuid,
                'created_at' => $group->created_at->toIso8601ZuluString(),
                'name' => $group->name,
                'description' => $group->description,
                'started_on' => $group->started_on?->format('Y-m-d'),
                'started_precision' => $group->started_precision->value,
                'ended_on' => $group->ended_on?->format('Y-m-d'),
                'ended_precision' => $group->ended_precision->value,
                'members' => $group->members
                    ->filter(fn (GroupMember $membership): bool => isset($this->contactUuids[$membership->contact_id]))
                    ->map(fn (GroupMember $membership): array => [
                        'contact' => $this->contactUuids[$membership->contact_id],
                        'role' => $membership->role,
                        'joined_on' => $membership->joined_on?->format('Y-m-d'),
                        'joined_precision' => $membership->joined_precision->value,
                        'left_on' => $membership->left_on?->format('Y-m-d'),
                        'left_precision' => $membership->left_precision->value,
                    ])
                    ->values()
                    ->all(),
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function lifeEvents(Vault $vault): Generator
    {
        $lifeEvents = LifeEvent::query()
            ->where('vault_id', $vault->id)
            ->with(['participants', 'fieldValues' => fn ($query) => $query->attached()->with(['row', 'file'])])
            ->lazyById(self::CHUNK);

        foreach ($lifeEvents as $lifeEvent) {
            yield [
                'uuid' => $lifeEvent->uuid,
                'created_at' => $lifeEvent->created_at->toIso8601ZuluString(),
                'type' => $this->lifeEventTypeNames[$lifeEvent->life_event_type_id] ?? null,
                'structure' => $this->lifeEventTypeVersions[$lifeEvent->structure_version_id] ?? null,
                'happened_on' => $lifeEvent->happened_on->format('Y-m-d'),
                'ended_on' => $lifeEvent->ended_on?->format('Y-m-d'),
                'date_precision' => $lifeEvent->date_precision->value,
                'happened_at_time' => $lifeEvent->happened_at_time,
                'duration_in_minutes' => $lifeEvent->duration_in_minutes,
                'timezone' => $lifeEvent->timezone,
                'title' => $lifeEvent->title,
                'narrative' => $lifeEvent->narrative,
                'location' => $lifeEvent->location,
                'latitude' => $lifeEvent->latitude,
                'longitude' => $lifeEvent->longitude,
                'is_shareable' => $lifeEvent->is_shareable,
                'narrative_is_shareable' => $lifeEvent->narrative_is_shareable,
                'participants' => $lifeEvent->participants
                    ->map(fn (LifeEventParticipant $participant): ?string => $this->recordUuid($participant->participant_type, $participant->participant_id))
                    ->filter()
                    ->values()
                    ->all(),
                'values' => $this->values($lifeEvent->fieldValues),
            ];
        }
    }

    /** @return Generator<int, array<string, mixed>> */
    private function reminders(Vault $vault): Generator
    {
        $reminders = Reminder::query()->where('vault_id', $vault->id)->with('fieldValue.row')->lazyById(self::CHUNK);

        foreach ($reminders as $reminder) {
            $record = $this->recordUuid($reminder->remindable_type, $reminder->remindable_id);

            if ($record === null) {
                continue;
            }

            $value = $reminder->fieldValue;

            yield [
                'uuid' => $reminder->uuid,
                'created_at' => $reminder->created_at->toIso8601ZuluString(),
                'record' => $record,
                'name' => $reminder->name,
                'source' => $reminder->source->value,
                'value' => $value === null ? null : [
                    'section' => $this->fieldOf($value->field_id)->sectionVersion->section->slug,
                    'field' => $value->field_key,
                    'row' => $value->row?->position,
                ],
                'starts_on' => $reminder->starts_on?->format('Y-m-d'),
                'cadence' => $reminder->cadence->value,
                'frequency_interval' => $reminder->frequency_interval,
                'time_of_day' => $reminder->time_of_day,
                'timezone' => $reminder->timezone,
                'is_active' => $reminder->is_active,
            ];
        }
    }

    private function putFiles(): void
    {
        $separator = '';

        foreach (array_chunk(array_keys($this->fileIds), self::CHUNK) as $ids) {
            foreach (File::query()->whereIn('id', $ids)->orderBy('id')->get() as $file) {
                $this->put($separator);
                $this->putFile($file);
                $separator = ',';
            }
        }
    }

    private function putFile(File $file): void
    {
        $described = [
            'uuid' => $file->uuid,
            'created_at' => $file->created_at->toIso8601ZuluString(),
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size_in_bytes' => $file->size_in_bytes,
            'checksum' => $file->checksum,
        ];

        $source = $file->disk()->readStream($file->path);

        if (! is_resource($source)) {
            $this->put($this->encode([...$described, 'data' => null]));

            return;
        }

        $this->put(substr($this->encode($described), 0, -1).',"data":"');

        stream_filter_append($source, 'convert.base64-encode', STREAM_FILTER_READ);
        stream_copy_to_stream($source, $this->stream);
        fclose($source);

        $this->put('"}');
    }

    /**
     * @param  Collection<int, FieldValue>  $values
     * @return list<array<string, mixed>>
     */
    private function values(Collection $values): array
    {
        return $values
            ->map(function (FieldValue $value): array {
                $field = $this->fieldOf($value->field_id);
                $column = $field->type->valueColumn();

                return [
                    'section' => $field->sectionVersion->section->slug,
                    'field' => $value->field_key,
                    'row' => $value->row?->position,
                    'parent_field' => $field->parent?->key,
                    'value' => match ($column) {
                        'value_string' => $value->value_string,
                        'value_text' => $value->value_text,
                        'value_number' => $value->value_number,
                        'value_boolean' => $value->value_boolean,
                        'value_date' => $value->value_date?->format('Y-m-d'),
                        'value_json' => $value->value_json,
                        default => null,
                    },
                    'label' => $column === 'value_list_item_id' || $value->value_list_item_id === null ? null : ($this->itemNames[$value->value_list_item_id] ?? null),
                    'label_wording' => $value->label_wording,
                    'year_is_unknown' => $value->year_is_unknown,
                    'month_is_unknown' => $value->month_is_unknown,
                    'file' => $this->fileReference($value->file),
                    'related_contact' => $value->related_contact_id === null ? null : ($this->contactUuids[$value->related_contact_id] ?? null),
                    'related_entity' => $value->related_entity_id === null ? null : ($this->entityUuids[$value->related_entity_id] ?? null),
                    'list_items' => $this->listItems($value, $column),
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function listItems(FieldValue $value, ?string $column): array
    {
        $ids = match ($column) {
            'value_list_item_id' => $value->value_list_item_id === null ? [] : [$value->value_list_item_id],
            'value_list_item_ids' => $value->value_list_item_ids ?? [],
            default => [],
        };

        $names = [];

        foreach ($ids as $id) {
            if (isset($this->itemNames[(int) $id])) {
                $names[] = $this->itemNames[(int) $id];
            }
        }

        return $names;
    }

    private function fieldOf(int $id): Field
    {
        return $this->fields[$id] ??= Field::query()->with(['sectionVersion.section', 'parent'])->findOrFail($id);
    }

    private function fileReference(?File $file): ?string
    {
        if ($file === null) {
            return null;
        }

        $this->fileIds[$file->id] = true;

        return $file->uuid;
    }

    private function recordUuid(string $type, int $id): ?string
    {
        return match ($type) {
            new Contact()->getMorphClass() => $this->contactUuids[$id] ?? null,
            new Entity()->getMorphClass() => $this->entityUuids[$id] ?? null,
            new LifeEvent()->getMorphClass() => $this->lifeEventUuids[$id] ?? null,
            default => null,
        };
    }

    private function put(string $json): void
    {
        fwrite($this->stream, $json);
    }

    /** @param  iterable<array<string, mixed>>  $entries */
    private function putList(string $key, iterable $entries): void
    {
        $this->put($this->encode($key).':[');

        $separator = '';

        foreach ($entries as $entry) {
            $this->put($separator.$this->encode($entry));
            $separator = ',';
        }

        $this->put(']');
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
