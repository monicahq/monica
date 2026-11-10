<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportSourceEnum;
use App\Exceptions\ImportFileIsNotReadable;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

/**
 * Read an export of this application once, from top to bottom, into the items an import writes
 * later: the structures and the types its records use, every record of every vault, and the
 * embedded files decoded beside the upload. Nothing reaches a vault here.
 */
class ReadMonicaFile
{
    public const string VERSION = WriteAccountExport::VERSION;

    private const int ITEMS_PER_INSERT = 75;

    private const array VALUE_KEYS = [
        'section', 'field', 'row', 'parent_field', 'value', 'label', 'label_wording', 'year_is_unknown',
        'month_is_unknown', 'file', 'related_contact', 'related_entity', 'list_items',
    ];

    private const array CONTACT_KEYS = [
        'created_at', 'is_partial', 'is_favourite', 'nickname', 'maiden_name', 'name_representations',
        'structure', 'avatar_file',
    ];

    private const array ENTITY_KEYS = ['created_at', 'entity_type', 'name', 'structure', 'avatar_file'];

    private const array RELATIONSHIP_KEYS = ['created_at', 'type', 'contact', 'other_contact', 'status', 'started_on', 'ended_on', 'notes'];

    private const array GROUP_KEYS = [
        'created_at', 'name', 'description', 'started_on', 'started_precision', 'ended_on', 'ended_precision', 'members',
        'notes', 'files', 'life_events', 'reminders',
    ];

    private const array LIFE_EVENT_KEYS = [
        'created_at', 'type', 'happened_on', 'ended_on', 'date_precision', 'happened_at_time', 'duration_in_minutes',
        'timezone', 'title', 'narrative', 'location', 'latitude', 'longitude', 'is_shareable', 'narrative_is_shareable',
        'participants', 'structure',
    ];

    private const array REMINDER_KEYS = [
        'created_at', 'record', 'name', 'source', 'value', 'starts_on', 'cadence', 'frequency_interval', 'time_of_day',
        'timezone', 'is_active',
    ];

    private const array RELATIONSHIP_TYPE_KEYS = ['category', 'name', 'role_a', 'role_b', 'is_symmetric'];

    private bool $versionRead = false;

    private ?string $vault = null;

    /** @var array<string, array{name: mixed, description: mixed, colour: mixed, me: string|null}> */
    private array $vaults = [];

    /** @var list<array<string, mixed>> */
    private array $structures = [];

    /** @var list<array<string, mixed>> */
    private array $lifeEventTypes = [];

    /** @var array<string, int> */
    private array $positions = [];

    /** @var array<string, array{found: int, left_out: int}> */
    private array $summary = [];

    /** @var list<array<string, mixed>> */
    private array $waitingItems = [];

    /** @var array{life_event_types: array<string, string>, relationship_types: array<string, string>} */
    private array $references = [
        'life_event_types' => [],
        'relationship_types' => [],
    ];

    public function __construct(
        private readonly AccountImport $import,
    ) {}

    public function execute(): void
    {
        $stream = Storage::disk($this->import->disk)->readStream($this->import->path);

        if (! is_resource($stream)) {
            throw new ImportFileIsNotReadable(__('The uploaded file is no longer there. Upload it again.'));
        }

        try {
            $this->import->items()->delete();

            $this->readTheFile($stream);
            $this->readWhatNeedsTheWholeFile();
            $this->insertTheWaitingItems();
            $this->record();
        } finally {
            fclose($stream);
        }
    }

    /** @param resource $stream */
    private function readTheFile($stream): void
    {
        $items = Items::fromStream($stream, [
            'pointer' => [
                '/version',
                '/account/structures',
                '/account/life_event_types',
                '/account/relationship_types',
                '/account/vaults/-/uuid',
                '/account/vaults/-/name',
                '/account/vaults/-/description',
                '/account/vaults/-/colour',
                '/account/vaults/-/contacts',
                '/account/vaults/-/entities',
                '/account/vaults/-/relationships',
                '/account/vaults/-/groups',
                '/account/vaults/-/life_events',
                '/account/vaults/-/reminders',
                '/account/vaults/-/files',
            ],
            'decoder' => new ExtJsonDecoder(true),
        ]);

        try {
            foreach ($items as $value) {
                $this->read($items->getMatchedJsonPointer(), $value);
            }
        } catch (PathNotFoundException) {
        } catch (JsonMachineException $exception) {
            Log::warning('An import file could not be read.', ['import' => $this->import->id, 'exception' => $exception->getMessage()]);

            throw new ImportFileIsNotReadable(__('The file could not be read. Try again, and if it fails again, export it again from where it came from.'));
        }

        if (! $this->versionRead) {
            throw new ImportFileIsNotReadable(__('This file is not an export Monica can read.'));
        }
    }

    private function read(string $pointer, mixed $value): void
    {
        $entry = is_array($value) ? $value : [];

        match ($pointer) {
            '/version' => $this->checkTheVersion($value),
            '/account/structures' => $this->structures[] = Arr::only($entry, ['template', 'version_number', 'document']),
            '/account/life_event_types' => $this->lifeEventTypes[] = Arr::only($entry, ['uuid', 'key', 'version_number', 'document']),
            '/account/relationship_types' => $this->readARelationshipType($entry),
            '/account/vaults/-/uuid' => $this->openTheVault((string) $value),
            '/account/vaults/-/name' => $this->vaults[(string) $this->vault]['name'] = $value,
            '/account/vaults/-/description' => $this->vaults[(string) $this->vault]['description'] = $value,
            '/account/vaults/-/colour' => $this->vaults[(string) $this->vault]['colour'] = $value,
            '/account/vaults/-/contacts' => $this->readAContact($entry),
            '/account/vaults/-/entities' => $this->readAnEntity($entry),
            '/account/vaults/-/relationships' => $this->readARelationship($entry),
            '/account/vaults/-/groups' => $this->readAGroup($entry),
            '/account/vaults/-/life_events' => $this->readALifeEvent($entry),
            '/account/vaults/-/reminders' => $this->readAReminder($entry),
            '/account/vaults/-/files' => $this->readAFile($entry),
            default => null,
        };
    }

    private function checkTheVersion(mixed $version): void
    {
        if ($version !== self::VERSION) {
            throw new ImportFileIsNotReadable(__('This file comes from a version of Monica this instance cannot read (:version).', ['version' => is_scalar($version) ? (string) $version : '']));
        }

        $this->versionRead = true;
    }

    /** @param array<string, mixed> $type */
    private function readARelationshipType(array $type): void
    {
        $uuid = (string) ($type['uuid'] ?? '');

        $this->references['relationship_types'][$uuid] = (string) ($type['name'] ?? '');

        $this->count('relationship_types');
        $this->add('relationship_type', $uuid, Arr::only($type, self::RELATIONSHIP_TYPE_KEYS));
    }

    private function openTheVault(string $uuid): void
    {
        $this->vault = $uuid;
        $this->vaults[$uuid] = ['name' => null, 'description' => null, 'colour' => null, 'me' => null];
    }

    /** @param array<string, mixed> $contact */
    private function readAContact(array $contact): void
    {
        $uuid = (string) ($contact['uuid'] ?? '');
        $display = collect(is_array($contact['name_representations'] ?? null) ? $contact['name_representations'] : [])
            ->first(fn (mixed $name): bool => is_array($name) && ($name['is_display'] ?? false) === true);
        $display = is_array($display) ? $display : [];

        $this->count('contacts');
        $this->add('contact', $uuid, [
            ...Arr::only($contact, self::CONTACT_KEYS),
            'properties' => [
                'first_name' => $display['given_name'] ?? null,
                'last_name' => $display['family_name'] ?? null,
                'is_partial' => (bool) ($contact['is_partial'] ?? false),
            ],
            'values' => $this->valuesKeptWith($uuid, $contact['values'] ?? []),
        ], name: implode(' ', array_filter([$display['given_name'] ?? null, $display['family_name'] ?? null], is_string(...))));

        if (($contact['is_me'] ?? false) === true) {
            $this->vaults[(string) $this->vault]['me'] = $uuid;
        }

        if (($contact['is_archived'] ?? false) === true) {
            $this->add('archive', $uuid, ['contact' => $uuid]);
        }
    }

    /** @param array<string, mixed> $entity */
    private function readAnEntity(array $entity): void
    {
        $uuid = (string) ($entity['uuid'] ?? '');

        $this->count('entities');
        $this->add('entity', $uuid, [
            ...Arr::only($entity, self::ENTITY_KEYS),
            'values' => $this->valuesKeptWith($uuid, $entity['values'] ?? []),
        ]);

        if (($entity['is_archived'] ?? false) === true) {
            $this->add('archive', $uuid, ['entity' => $uuid]);
        }
    }

    /** @param array<string, mixed> $relationship */
    private function readARelationship(array $relationship): void
    {
        $this->count('relationships');
        $this->add('relationship', $relationship['uuid'] ?? null, Arr::only($relationship, self::RELATIONSHIP_KEYS));
    }

    /** @param array<string, mixed> $group */
    private function readAGroup(array $group): void
    {
        $this->count('groups');
        $this->add('group', $group['uuid'] ?? null, Arr::only($group, self::GROUP_KEYS), name: is_string($group['name'] ?? null) ? $group['name'] : null);
    }

    /** @param array<string, mixed> $lifeEvent */
    private function readALifeEvent(array $lifeEvent): void
    {
        $uuid = (string) ($lifeEvent['uuid'] ?? '');

        $this->count('life_events');
        $this->add('life_event', $uuid, [
            ...Arr::only($lifeEvent, self::LIFE_EVENT_KEYS),
            'values' => $this->valuesKeptWith($uuid, $lifeEvent['values'] ?? []),
        ]);
    }

    /** @param array<string, mixed> $reminder */
    private function readAReminder(array $reminder): void
    {
        $this->count('reminders');
        $this->add('reminder', $reminder['uuid'] ?? null, Arr::only($reminder, self::REMINDER_KEYS));
    }

    /** @param array<string, mixed> $file */
    private function readAFile(array $file): void
    {
        $uuid = (string) ($file['uuid'] ?? '');
        $data = $file['data'] ?? null;

        if (is_string($data) && intdiv(strlen($data), 4) * 3 > (int) config('monica.max_file_size_in_kilobytes') * 1024) {
            $this->count('files', leftOut: true);
            $this->add('file', Str::isUuid($uuid) ? $uuid : null, ['original_filename' => $file['original_name'] ?? null], AccountImportReasonEnum::FileTooLarge);

            return;
        }

        $bytes = is_string($data) ? base64_decode($data, true) : false;

        if (! Str::isUuid($uuid) || $bytes === false || $bytes === '') {
            $this->count('files', leftOut: true);
            $this->add('file', Str::isUuid($uuid) ? $uuid : null, ['original_filename' => $file['original_name'] ?? null], AccountImportReasonEnum::FileNotDecodable);

            return;
        }

        $path = $this->import->directory().'/files/'.Str::uuid7();
        Storage::disk($this->import->disk)->put($path, $bytes);

        $this->count('files');
        $this->add('file', $uuid, [
            'original_filename' => (string) ($file['original_name'] ?? $uuid),
            'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
            'path' => $path,
            'size_in_bytes' => strlen($bytes),
            'created_at' => $file['created_at'] ?? null,
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function valuesKeptWith(string $uuid, mixed $values): array
    {
        $values = array_values(array_map(
            fn (mixed $value): array => Arr::only(is_array($value) ? $value : [], self::VALUE_KEYS),
            is_array($values) ? $values : [],
        ));

        $pointsAtAnotherRecord = collect($values)->contains(
            fn (array $value): bool => ($value['related_contact'] ?? null) !== null || ($value['related_entity'] ?? null) !== null,
        );

        if (! $pointsAtAnotherRecord) {
            return $values;
        }

        $this->add('related_values', $uuid, ['record' => $uuid, 'values' => $values]);

        return [];
    }

    private function readWhatNeedsTheWholeFile(): void
    {
        $this->addTheStructures();

        $holdsSeveralVaults = count($this->vaults) > 1;

        foreach ($this->vaults as $uuid => $vault) {
            $this->vault = $uuid;

            $this->count('vaults');
            $this->add('vault', $uuid, Arr::only($vault, ['name', 'description', 'colour']));

            if ($holdsSeveralVaults && $vault['me'] !== null) {
                $this->add('me', $vault['me'], []);
            }
        }

        $this->vault = null;
    }

    private function addTheStructures(): void
    {
        $structures = collect($this->structures)->sortBy(fn (array $structure): int => (int) ($structure['version_number'] ?? 0));

        foreach ($structures as $structure) {
            $this->count('templates');
            $this->add('structure', ($structure['template'] ?? '').':'.($structure['version_number'] ?? ''), $structure);
        }

        $lifeEventTypes = collect($this->lifeEventTypes)->sortBy(fn (array $type): int => (int) ($type['version_number'] ?? 0));

        foreach ($lifeEventTypes as $type) {
            $key = is_string($type['key'] ?? null) ? $type['key'] : null;
            $name = $key ?? (string) ($type['uuid'] ?? '');

            if ($key === null) {
                $this->references['life_event_types'][$name] = (string) (Arr::get($type, 'document.life_event_type.name') ?? Arr::get($type, 'document.life_event_type.name_translation_key') ?? '');
            }

            $this->count('life_event_types');
            $this->add('life_event_type', $name.':'.($type['version_number'] ?? ''), [
                'type' => $name,
                'key' => $key,
                'document' => $type['document'] ?? null,
            ]);
        }
    }

    private function record(): void
    {
        $this->import->summary = $this->summary;
        $this->import->source_references = $this->references;
        $this->import->source_format = AccountImportSourceEnum::Monica;
        $this->import->me_source_uuid = count($this->vaults) === 1 ? array_values($this->vaults)[0]['me'] : null;
        $this->import->items_total = $this->import->items()->whereNull('outcome')->count();
        $this->import->save();
    }

    private function count(string $kind, bool $leftOut = false): void
    {
        $this->summary[$kind] ??= ['found' => 0, 'left_out' => 0];
        $this->summary[$kind]['found']++;

        if ($leftOut) {
            $this->summary[$kind]['left_out']++;
        }
    }

    /** @param array<string, mixed> $payload */
    private function add(string $kind, mixed $sourceUuid, array $payload, ?AccountImportReasonEnum $leftOutBecause = null, ?string $name = null): void
    {
        $rank = AccountImportItem::RANKS[$kind];
        $this->positions[$kind] = ($this->positions[$kind] ?? 0) + 1;

        $this->waitingItems[] = [
            'account_id' => $this->import->account_id,
            'account_import_id' => $this->import->id,
            'vault_source_uuid' => $this->vault,
            'kind' => $kind,
            'source_uuid' => is_scalar($sourceUuid) && (string) $sourceUuid !== '' ? (string) $sourceUuid : null,
            'name' => $name === null || $name === '' ? null : mb_substr($name, 0, 255),
            'position' => $rank * AccountImportItem::RANK_SPAN + $this->positions[$kind],
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'outcome' => $leftOutBecause === null ? null : AccountImportOutcomeEnum::LeftOut->value,
            'reason' => $leftOutBecause?->value,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (count($this->waitingItems) >= self::ITEMS_PER_INSERT) {
            $this->insertTheWaitingItems();
        }
    }

    private function insertTheWaitingItems(): void
    {
        if ($this->waitingItems === []) {
            return;
        }

        AccountImportItem::query()->insert($this->waitingItems);

        $this->waitingItems = [];
    }
}
