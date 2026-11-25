<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\DatePrecisionEnum;
use App\Exceptions\ImportFileIsNotReadable;
use App\Helpers\MonicaFourVocabulary;
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
 * Read a Monica 4 export once, from top to bottom, into the items an import writes later: one
 * row per thing worth writing, the embedded files decoded beside the upload, and a summary of
 * what the file holds and what will not come over. Nothing reaches a vault here, and nothing the
 * file says about a credential is kept.
 */
class ReadMonicaFourFile
{
    public const string VERSION = '1.0-preview.1';

    private const int ITEMS_PER_INSERT = 75;

    private const array CONTACT_PROPERTIES = [
        'first_name', 'middle_name', 'last_name', 'nickname', 'description', 'job', 'company',
        'food_preferences', 'tags', 'is_starred', 'is_partial', 'gender', 'birthdate',
    ];

    private string $section = '';

    private bool $versionRead = false;

    private ?string $exportedBy = null;

    private ?string $meContact = null;

    /** @var array<string, int> */
    private array $positions = [];

    /** @var array<string, array{found: int, left_out: int}> */
    private array $summary = [];

    /** @var list<array<string, mixed>> */
    private array $waitingItems = [];

    /** @var array<string, true> */
    private array $contacts = [];

    /** @var array<string, list<string>> */
    private array $activityContacts = [];

    /** @var list<array<string, mixed>> */
    private array $relationships = [];

    /** @var array<string, true> */
    private array $naturesUsed = [];

    /** @var array<string, true> */
    private array $ownNaturesUsed = [];

    /** @var array{genders: array<string, string|null>, contact_field_types: array<string, array<string, mixed>>, life_event_types: array<string, array<string, mixed>>, life_event_categories: array<string, string|null>, activity_types: array<string, array<string, mixed>>} */
    private array $references = [
        'genders' => [],
        'contact_field_types' => [],
        'life_event_types' => [],
        'life_event_categories' => [],
        'activity_types' => [],
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
            'pointer' => ['/version', '/exported_by', '/account/data/-/type', '/account/data/-/values', '/account/properties', '/account/instance'],
            'decoder' => new ExtJsonDecoder(true),
        ]);

        try {
            foreach ($items as $key => $value) {
                $this->read($items->getMatchedJsonPointer(), (string) $key, $value);
            }
        } catch (PathNotFoundException) {
            // Raised at the end of the file when a section is absent; what was present has been read.
        } catch (JsonMachineException $exception) {
            Log::warning('An import file could not be read.', ['import' => $this->import->id, 'exception' => $exception->getMessage()]);

            throw new ImportFileIsNotReadable(__('This file is not complete. Export it again from Monica 4 and upload the new file.'));
        }

        if (! $this->versionRead) {
            throw new ImportFileIsNotReadable(__('This file is not a Monica 4 export.'));
        }
    }

    private function read(string $pointer, string $key, mixed $value): void
    {
        switch ($pointer) {
            case '/version':
                $this->checkTheVersion($value);
                break;
            case '/exported_by':
                $this->exportedBy = is_string($value) ? $value : null;
                break;
            case '/account/data/-/type':
                $this->section = (string) $value;
                break;
            case '/account/data/-/values':
                $this->readRecord(is_array($value) ? $value : []);
                break;
            case '/account/properties':
                $this->readProperty($key, $value);
                break;
            case '/account/instance':
                $this->readReference($key, is_array($value) ? $value : []);
                break;
            default:
                break;
        }
    }

    private function checkTheVersion(mixed $version): void
    {
        $this->versionRead = true;

        if ($version !== self::VERSION) {
            throw new ImportFileIsNotReadable(__('This file comes from a version of Monica this instance cannot read (:version).', ['version' => is_scalar($version) ? (string) $version : '']));
        }
    }

    /** @param array<string, mixed> $record */
    private function readRecord(array $record): void
    {
        match ($this->section) {
            'user' => $this->readUser($record),
            'contact' => $this->readContact($record),
            'relationship' => $this->relationships[] = $record,
            'photo', 'document' => $this->readFile($record),
            'activity' => $this->readActivity($record),
            'addressbook' => $this->count('address_books', leftOut: true),
            'addressbook_subscription' => $this->count('address_book_subscriptions', leftOut: true),
            default => $this->count($this->section, leftOut: true),
        };
    }

    /** @param  array<string, mixed>  $user */
    private function readUser(array $user): void
    {
        $this->count('users', leftOut: true);

        $meContact = Arr::get($user, 'properties.me_contact');

        if (! is_string($meContact)) {
            return;
        }

        if ($this->meContact === null || ($user['uuid'] ?? null) === $this->exportedBy) {
            $this->meContact = $meContact;
        }
    }

    /** @param array<string, mixed> $contact */
    private function readContact(array $contact): void
    {
        $uuid = (string) ($contact['uuid'] ?? '');
        $properties = is_array($contact['properties'] ?? null) ? $contact['properties'] : [];
        $data = $this->dataOf($contact);

        $this->contacts[$uuid] = true;
        $this->count('contacts');

        $this->add('contact', $uuid, [
            'created_at' => $contact['created_at'] ?? null,
            'properties' => [
                ...Arr::only($properties, self::CONTACT_PROPERTIES),
                'avatar_photo' => Arr::get($properties, 'avatar.avatar_source') === 'photo' ? Arr::get($properties, 'avatar.avatar_photo') : null,
            ],
            'contact_fields' => $this->propertiesOf($data['contact_field'] ?? []),
            'addresses' => $this->propertiesOf($data['address'] ?? []),
            'notes' => $this->propertiesOf($data['note'] ?? []),
            'photos' => $data['photo'] ?? [],
            'documents' => $data['document'] ?? [],
            'gifts' => $this->propertiesOf($data['gift'] ?? []),
            'debts' => $this->propertiesOf($data['debt'] ?? []),
            'tasks' => $this->propertiesOf($data['task'] ?? []),
        ], name: implode(' ', array_filter([$properties['first_name'] ?? null, $properties['last_name'] ?? null], is_string(...))));

        $this->count('contact_fields', amount: count($data['contact_field'] ?? []));
        $this->count('addresses', amount: count($data['address'] ?? []));
        $this->count('notes', amount: count($data['note'] ?? []));
        $this->count('gifts', amount: count($data['gift'] ?? []));
        $this->count('debts', amount: count($data['debt'] ?? []));
        $this->count('tasks', amount: count($data['task'] ?? []));
        $this->count('tags', amount: count(is_array($properties['tags'] ?? null) ? $properties['tags'] : []));

        $this->readReminders($uuid, $properties, $data['reminder'] ?? []);
        $this->readLifeEvents($uuid, $properties, $data['life_event'] ?? []);
        $this->readConversations($uuid, $data['call'] ?? [], $data['conversation'] ?? []);
        $this->readPets($uuid, $data['pet'] ?? []);

        foreach ($data['activity'] ?? [] as $activity) {
            $this->activityContacts[(string) $activity][] = $uuid;
        }

        if (($properties['is_active'] ?? true) === false) {
            $this->add('archive', $uuid, ['contact' => $uuid]);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  list<array<string, mixed>>  $reminders
     */
    private function readReminders(string $contact, array $properties, array $reminders): void
    {
        $birthdate = Arr::get($properties, 'birthdate.date');

        if (is_array($properties['deceased_reminder'] ?? null)) {
            $reminders[] = $properties['deceased_reminder'];
        }

        foreach ($reminders as $reminder) {
            $this->count('reminders');
            $this->add('reminder', $reminder['uuid'] ?? null, [
                'contact' => $contact,
                'reminder' => $reminder['properties'] ?? [],
                'birthdate' => $birthdate,
                'created_at' => $reminder['created_at'] ?? null,
            ]);
        }

        $days = (int) ($properties['stay_in_touch_frequency'] ?? 0);

        if ($days > 0) {
            $this->count('reminders');
            $this->add('reminder', null, ['contact' => $contact, 'stay_in_touch_days' => $days]);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  list<array<string, mixed>>  $lifeEvents
     */
    private function readLifeEvents(string $contact, array $properties, array $lifeEvents): void
    {
        foreach ($lifeEvents as $lifeEvent) {
            $type = (string) Arr::get($lifeEvent, 'properties.type', '');
            $this->naturesUsed[$type] = true;

            $this->count('life_events');
            $this->add('life_event', $lifeEvent['uuid'] ?? null, [
                ...Arr::only($lifeEvent['properties'] ?? [], ['name', 'note', 'happened_at']),
                'contact' => $contact,
                'type' => $type,
                'created_at' => $lifeEvent['created_at'] ?? null,
            ]);
        }

        if (($properties['is_dead'] ?? false) === true && is_array($properties['deceased_date'] ?? null)) {
            $this->ownNaturesUsed['death'] = true;

            $this->count('life_events');
            $this->add('life_event', null, [
                'contact' => $contact,
                'nature' => 'death',
                'happened_at' => $properties['deceased_date']['date'] ?? null,
                'date_precision' => $this->precisionOf($properties['deceased_date']),
                'created_at' => $properties['deceased_date']['created_at'] ?? null,
            ]);
        }

        if (is_array($properties['first_met_date'] ?? null)) {
            $this->ownNaturesUsed['first_meeting'] = true;

            $this->count('life_events');
            $this->add('life_event', null, [
                'contact' => $contact,
                'nature' => 'first_meeting',
                'happened_at' => $properties['first_met_date']['date'] ?? null,
                'date_precision' => $this->precisionOf($properties['first_met_date']),
                'participant' => $properties['first_met_through'] ?? null,
                'created_at' => $properties['first_met_date']['created_at'] ?? null,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $calls
     * @param  list<array<string, mixed>>  $conversations
     */
    private function readConversations(string $contact, array $calls, array $conversations): void
    {
        foreach ($calls as $call) {
            $this->count('calls');
            $this->add('call', $call['uuid'] ?? null, [
                ...Arr::only($call['properties'] ?? [], ['called_at', 'content']),
                'contact' => $contact,
                'created_at' => $call['created_at'] ?? null,
            ]);
        }

        foreach ($conversations as $conversation) {
            $messages = Arr::get($conversation, 'properties.messages', []);

            $this->count('conversations');
            $this->add('conversation', $conversation['uuid'] ?? null, [
                'contact' => $contact,
                'happened_at' => Arr::get($conversation, 'properties.happened_at'),
                'messages' => array_map(fn (array $message): array => [
                    'content' => (string) Arr::get($message, 'properties.content', ''),
                    'written_by_me' => (bool) Arr::get($message, 'properties.written_by_me', false),
                ], is_array($messages) ? $messages : []),
                'created_at' => $conversation['created_at'] ?? null,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $pets */
    private function readPets(string $contact, array $pets): void
    {
        foreach ($pets as $pet) {
            $this->count('pets');
            $this->add('pet', $pet['uuid'] ?? null, [
                ...Arr::only($pet['properties'] ?? [], ['name', 'category']),
                'contact' => $contact,
                'created_at' => $pet['created_at'] ?? null,
            ]);
        }
    }

    /** @param  array<string, mixed>  $file */
    private function readFile(array $file): void
    {
        $uuid = (string) ($file['uuid'] ?? '');
        $properties = is_array($file['properties'] ?? null) ? $file['properties'] : [];
        $dataUrl = (string) ($properties['dataUrl'] ?? '');
        $comma = strpos($dataUrl, ',');
        $kind = $this->section === 'photo' ? 'photos' : 'documents';

        if (intdiv(strlen($dataUrl), 4) * 3 > (int) config('monica.max_file_size_in_kilobytes') * 1024) {
            $this->count($kind, leftOut: true);
            $this->add('file', Str::isUuid($uuid) ? $uuid : null, ['original_filename' => $properties['original_filename'] ?? null], AccountImportReasonEnum::FileTooLarge);

            return;
        }

        $bytes = $comma === false ? false : base64_decode(substr($dataUrl, $comma + 1), true);

        if (! Str::isUuid($uuid) || $bytes === false || $bytes === '') {
            $this->count($kind, leftOut: true);
            $this->add('file', Str::isUuid($uuid) ? $uuid : null, ['original_filename' => $properties['original_filename'] ?? null], AccountImportReasonEnum::FileNotDecodable);

            return;
        }

        $path = $this->import->directory().'/files/'.Str::uuid7();
        Storage::disk($this->import->disk)->put($path, $bytes);

        $this->count($kind);
        $this->add('file', $uuid, [
            'original_filename' => (string) ($properties['original_filename'] ?? $uuid),
            'mime_type' => (string) ($properties['mime_type'] ?? 'application/octet-stream'),
            'path' => $path,
            'size_in_bytes' => strlen($bytes),
            'created_at' => $file['created_at'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $activity */
    private function readActivity(array $activity): void
    {
        $uuid = (string) ($activity['uuid'] ?? '');

        $this->count('activities');
        $this->add('activity', $uuid, [
            ...Arr::only($activity['properties'] ?? [], ['summary', 'description', 'happened_at', 'type']),
            'participants' => $this->activityContacts[$uuid] ?? [],
            'created_at' => $activity['created_at'] ?? null,
        ]);
    }

    private function readProperty(string $key, mixed $value): void
    {
        $entries = is_array($value) ? $value : [];

        if ($key === 'journal_entries') {
            foreach ($entries as $entry) {
                $this->count(Arr::get($entry, 'properties.type') === 'day' ? 'day_ratings' : 'journal_entries');
                $this->add('journal_entry', $entry['uuid'] ?? null, [...$entry['properties'] ?? [], 'created_at' => $entry['created_at'] ?? null]);
            }

            return;
        }

        if (in_array($key, ['modules', 'reminder_rules', 'audit_logs'], true)) {
            $this->count($key, leftOut: true, amount: count($entries));
        }
    }

    /** @param list<array<string, mixed>> $entries */
    private function readReference(string $key, array $entries): void
    {
        foreach ($entries as $entry) {
            $uuid = (string) ($entry['uuid'] ?? '');
            $properties = is_array($entry['properties'] ?? null) ? $entry['properties'] : [];

            match ($key) {
                'genders' => $this->references['genders'][$uuid] = $properties['type'] ?? null,
                'contact_field_types' => $this->references['contact_field_types'][$uuid] = Arr::only($properties, ['name', 'type', 'protocol']),
                'life_event_types' => $this->references['life_event_types'][$uuid] = ['key' => $properties['translation_key'] ?? null, 'category' => $properties['category'] ?? null],
                'life_event_categories' => $this->references['life_event_categories'][$uuid] = $properties['translation_key'] ?? null,
                'activity_types' => $this->references['activity_types'][$uuid] = Arr::only($properties, ['translation_key', 'name']),
                default => null,
            };
        }
    }

    private function readWhatNeedsTheWholeFile(): void
    {
        $this->pairTheRelationships();
        $this->askForTheNatures();

        if ($this->meContact !== null && ! isset($this->contacts[$this->meContact])) {
            $this->meContact = null;
        }
    }

    private function pairTheRelationships(): void
    {
        $seen = [];
        $ownTypes = [];

        foreach ($this->relationships as $relationship) {
            $properties = is_array($relationship['properties'] ?? null) ? $relationship['properties'] : [];
            $type = (string) ($properties['type'] ?? '');
            $one = (string) ($properties['contact_is'] ?? '');
            $other = (string) ($properties['of_contact'] ?? '');
            $pair = min($type, MonicaFourVocabulary::reverseOf($type));
            $link = min($one, $other).'|'.max($one, $other).'|'.(MonicaFourVocabulary::relationshipType($type)[0]->value ?? $pair);

            $payload = [...$properties, 'created_at' => $relationship['created_at'] ?? null];

            $this->count('relationships', leftOut: isset($seen[$link]));

            if (isset($seen[$link])) {
                $this->add('relationship', $relationship['uuid'] ?? null, $payload, AccountImportReasonEnum::ReverseOfAnotherRelationship);

                continue;
            }

            $seen[$link] = true;
            $this->add('relationship', $relationship['uuid'] ?? null, $payload);

            if (MonicaFourVocabulary::relationshipType($type) === null) {
                $ownTypes[$pair] = true;
            }
        }

        foreach (array_keys($ownTypes) as $pair) {
            $this->add('relationship_type', null, ['name' => $pair]);
        }
    }

    private function askForTheNatures(): void
    {
        $needed = $this->ownNaturesUsed;

        foreach (array_keys($this->naturesUsed) as $uuid) {
            $key = (string) ($this->references['life_event_types'][$uuid]['key'] ?? '');

            if ($key !== '' && MonicaFourVocabulary::nature($key) === null) {
                $needed[$key] = true;
            }
        }

        foreach (array_keys($needed) as $key) {
            $category = null;

            foreach ($this->references['life_event_types'] as $type) {
                if ($type['key'] === $key) {
                    $category = $this->references['life_event_categories'][$type['category']] ?? null;
                }
            }

            $this->add('life_event_type', null, [
                'key' => $key,
                'category' => $category ?? 'family_relationships',
            ]);
        }
    }

    private function record(): void
    {
        $this->import->summary = $this->summary;
        $this->import->source_references = $this->references;
        $this->import->source_format = AccountImportSourceEnum::MonicaFour;
        $this->import->me_source_uuid = $this->meContact;
        $this->import->items_total = $this->import->items()->whereNull('outcome')->count();
        $this->import->save();
    }

    /** @param array<string, mixed> $date */
    private function precisionOf(array $date): string
    {
        if (($date['is_year_unknown'] ?? false) === true) {
            return DatePrecisionEnum::Unknown->value;
        }

        if (($date['is_age_based'] ?? false) === true) {
            return DatePrecisionEnum::Year->value;
        }

        return DatePrecisionEnum::Day->value;
    }

    /**
     * @param  array<string, mixed>  $contact
     * @return array<string, list<mixed>>
     */
    private function dataOf(array $contact): array
    {
        $data = [];

        foreach (is_array($contact['data'] ?? null) ? $contact['data'] : [] as $section) {
            $type = (string) ($section['type'] ?? '');
            $data[$type] = [...($data[$type] ?? []), ...(is_array($section['values'] ?? null) ? $section['values'] : [])];
        }

        return $data;
    }

    /**
     * @param  list<mixed>  $records
     * @return list<array<string, mixed>>
     */
    private function propertiesOf(array $records): array
    {
        return array_values(array_map(
            function (mixed $record): array {
                if (! is_array($record)) {
                    return [];
                }

                $properties = is_array($record['properties'] ?? null) ? $record['properties'] : [];

                return [...$properties, 'created_at' => $record['created_at'] ?? null];
            },
            $records,
        ));
    }

    private function count(string $kind, bool $leftOut = false, int $amount = 1): void
    {
        if ($amount === 0) {
            return;
        }

        $this->summary[$kind] ??= ['found' => 0, 'left_out' => 0];
        $this->summary[$kind]['found'] += $amount;

        if ($leftOut) {
            $this->summary[$kind]['left_out'] += $amount;
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
            'vault_source_uuid' => null,
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
