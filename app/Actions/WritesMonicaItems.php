<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportReasonEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\LifeEventSourceEnum;
use App\Enums\RelationshipCategoryEnum;
use App\Enums\RelationshipStatusEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\RelationshipIsAlreadyRecorded;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ValueListItemIsStopped;
use App\Helpers\StructureFileReader;
use App\Helpers\StructureWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AccountImportItem;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * How one item of an export of this application is written, for the action writing an item of an import.
 */
trait WritesMonicaItems
{
    private function writeAMonicaItem(): void
    {
        match ($this->item->kind) {
            'vault' => $this->writeAVault(),
            'structure' => $this->writeATemplateVersion(),
            'life_event_type' => $this->writeAMonicaLifeEventType(),
            'relationship_type' => $this->writeAMonicaRelationshipType(),
            'file' => $this->writeAFile(),
            'contact' => $this->writeAMonicaContact(),
            'entity' => $this->writeAnEntity(),
            'life_event' => $this->writeAMonicaLifeEvent(),
            'related_values' => $this->writeTheRelatedValues(),
            'reminder' => $this->writeAMonicaReminder(),
            'relationship' => $this->writeAMonicaRelationship(),
            'group' => $this->writeAMonicaGroup(),
            'me' => null,
            'archive' => $this->archive(),
            default => $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting),
        };
    }

    private function writeAVault(): void
    {
        if ($this->import->vault_id !== null) {
            $this->record = $this->vault();

            return;
        }

        $vault = new CreateVault(
            user: $this->user,
            name: (string) $this->shortText($this->payload('name')),
            description: $this->text($this->payload('description')),
            colour: $this->shortText($this->payload('colour')),
        )->execute();

        $this->import->created_vault_ids = [...$this->import->created_vault_ids, $vault->id];
        $this->import->save();

        $this->record = $vault;
    }

    private function writeATemplateVersion(): void
    {
        $raw = json_encode($this->payload('document'), JSON_THROW_ON_ERROR);
        $file = StructureFileReader::read($raw, expecting: 'template');
        $earlier = $this->latestCopyOf('structure', (string) $this->payload('template'));
        $valueLists = (int) ValueList::withTrashed()->where('account_id', $this->user->account_id)->max('id');
        $entityTypes = (int) EntityType::withTrashed()->where('account_id', $this->user->account_id)->max('id');

        $version = $earlier instanceof StructureVersion
            ? $this->nextVersionOf($earlier, $file, $file['template']['pages'])
            : new ImportTemplate(user: $this->user, raw: $raw)->execute()->structure?->versions()->sole();

        if (! $version instanceof StructureVersion) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        new PublishStructureVersion(
            user: $this->user,
            structureVersion: $version,
            changeNote: $version->previous_version_id === null ? null : __('Imported from an export.'),
        )->execute();

        $this->record = $version;
        $this->keptPayload = $this->whatTheCopyMade($version, $file['template']['pages'], $valueLists, $entityTypes);
    }

    /**
     * @param  array<string, mixed>  $file
     * @param  list<array<string, mixed>>  $pages
     */
    private function nextVersionOf(StructureVersion $earlier, array $file, array $pages): StructureVersion
    {
        $writer = new StructureWriter($this->user, $file);
        $writer->settleTheValueLists();

        $version = StructureVersion::query()->create([
            'account_id' => $this->user->account_id,
            'structure_id' => $earlier->structure_id,
            'version_number' => $earlier->version_number + 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => $earlier->id,
            'record_count' => 0,
        ]);

        foreach ($pages as $payload) {
            $page = StructureVersionPage::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $version->id,
                'key' => $payload['key'],
                'label' => TextSanitizer::plainText($payload['label']),
                'label_translation_key' => null,
                'position' => $payload['position'],
            ]);

            foreach ($payload['sections'] as $row) {
                $sectionVersion = $writer->section($row['section']);

                StructureVersionSection::query()->create([
                    'account_id' => $this->user->account_id,
                    'structure_version_id' => $version->id,
                    'structure_version_page_id' => $page->id,
                    'section_version_id' => $sectionVersion->id,
                    'position' => $row['position'],
                    'title_override' => TextSanitizer::nullablePlainText($row['title_override']),
                    'is_collapsed_by_default' => $row['is_collapsed_by_default'],
                ]);

                $sectionVersion->increment('usage_count');
            }
        }

        return $version;
    }

    private function writeAMonicaLifeEventType(): void
    {
        $document = $this->payload('document');

        if (! is_array($document)) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        $raw = json_encode($document, JSON_THROW_ON_ERROR);
        $file = StructureFileReader::read($raw, expecting: 'life_event_type');
        $pages = $file['life_event_type']['pages'];
        $earlier = $this->latestCopyOf('life_event_type', (string) $this->payload('type'));

        if (! $earlier instanceof StructureVersion && $this->holdsEveryField($this->ownLifeEventType(), $pages)) {
            return;
        }

        $valueLists = (int) ValueList::withTrashed()->where('account_id', $this->user->account_id)->max('id');
        $entityTypes = (int) EntityType::withTrashed()->where('account_id', $this->user->account_id)->max('id');

        $version = $earlier instanceof StructureVersion
            ? $this->nextVersionOf($earlier, $file, $pages)
            : new ImportLifeEventType(user: $this->user, raw: $raw)->execute()->structure?->publishedVersion;

        if (! $version instanceof StructureVersion) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        if ($earlier instanceof StructureVersion) {
            new PublishStructureVersion(user: $this->user, structureVersion: $version, changeNote: __('Imported from an export.'))->execute();
        }

        $this->record = $version;
        $this->keptPayload = $this->whatTheCopyMade($version, $pages, $valueLists, $entityTypes);
    }

    private function ownLifeEventType(): ?LifeEventType
    {
        $key = $this->payload('key');

        if (is_string($key)) {
            return LifeEventType::query()
                ->where('account_id', $this->user->account_id)
                ->where('key', $key)
                ->first();
        }

        return LifeEventType::query()
            ->where('account_id', $this->user->account_id)
            ->whereNull('key')
            ->where('name', (string) ($this->reference('life_event_types')[(string) $this->payload('type')] ?? ''))
            ->first();
    }

    /** @param list<array<string, mixed>> $pages */
    private function holdsEveryField(?LifeEventType $lifeEventType, array $pages): bool
    {
        $version = $lifeEventType?->structure?->publishedVersion;

        if (! $version instanceof StructureVersion) {
            return false;
        }

        $held = $this->fieldsByFilePath($version);

        foreach ($pages as $page) {
            foreach ($page['sections'] as $row) {
                foreach ($row['section']['fields'] as $field) {
                    $own = $held["{$row['section']['slug']}/{$field['key']}"] ?? null;

                    if (! $own instanceof Field || $own->type->value !== $field['type']) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function writeAMonicaRelationshipType(): void
    {
        $name = (string) $this->shortText($this->payload('name'));

        $isAlreadyThere = RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->where('name', $name)
            ->exists();

        if ($isAlreadyThere) {
            return;
        }

        $categories = RelationshipCategory::query()->where('account_id', $this->user->account_id)->get();
        $wanted = $this->payload('category');

        $this->record = new CreateRelationshipType(
            user: $this->user,
            relationshipCategory: $categories->firstWhere('key', $wanted)
                ?? $categories->firstWhere('name', $wanted)
                ?? $categories->firstWhere('key', RelationshipCategoryEnum::Other->value)
                ?? $categories->firstOrFail(),
            name: $name,
            roleA: (string) $this->shortText($this->payload('role_a')),
            roleB: (string) $this->shortText($this->payload('role_b')),
            isSymmetric: (bool) $this->payload('is_symmetric', false),
        )->execute();
    }

    private function writeAMonicaContact(): void
    {
        if ($this->joinsMe($this->item->source_uuid)) {
            $me = $this->meOfTheVault();
            $this->writeIntoMe($me, fn () => $this->writeTheValuesAndAvatarOf($me));

            return;
        }

        $names = collect((array) $this->payload('name_representations', []))
            ->filter(fn (mixed $name): bool => is_array($name))
            ->values();

        $contact = new CreateContact(
            user: $this->user,
            vault: $this->vault(),
            structureVersion: $this->copyOf($this->payload('structure')),
            maidenName: $this->shortText($this->payload('maiden_name')),
            nickname: $this->shortText($this->payload('nickname')),
            isPartial: (bool) $this->payload('is_partial', false),
            isFavourite: (bool) $this->payload('is_favourite', false),
            nameRepresentations: $names->isEmpty() ? null : $names->map(fn (array $name): array => [
                ...Arr::only($name, ['kind', 'phonetic_system', 'formatted_name', 'prefix', 'given_name', 'additional_name', 'family_name', 'suffix', 'name_order']),
                'language' => $name['language_tag'] ?? null,
                'script' => $name['script_code'] ?? null,
            ])->all(),
            display: (int) $names->search(fn (array $name): bool => ($name['is_display'] ?? false) === true),
        )->execute();

        $this->record = $contact;
        $this->removeTheDefaultValues($contact);
        $this->writeTheValuesAndAvatarOf($contact);
    }

    private function writeTheValuesAndAvatarOf(Contact $contact): void
    {
        $this->writeTheValues($contact, (array) $this->payload('values', []));
        $this->putTheAvatar($contact, $this->fileOf($this->payload('avatar_file')));

        if ($this->valuesNotWritten > 0) {
            $this->reason = AccountImportReasonEnum::PartlyWritten;
        }
    }

    private function writeAnEntity(): void
    {
        $version = $this->copyOf($this->payload('structure'));
        $entityType = $version?->structure->entityType ?? EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->where('slug', (string) $this->payload('entity_type'))
            ->first();
        $version ??= $entityType?->versionsOnOfferIn($this->vault())->first();

        if (! $entityType instanceof EntityType || ! $version instanceof StructureVersion) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        $entity = Entity::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault()->id,
            'entity_type_id' => $entityType->id,
            'structure_version_id' => $version->id,
            'name' => TextSanitizer::plainText((string) $this->shortText($this->payload('name'))),
            'avatar_colour' => Arr::random(Entity::AVATAR_COLOURS),
        ]);

        $version->increment('record_count');
        $entityType->increment('entity_count');

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityCreation,
            vault: $this->vault(),
            parameters: ['name' => $entity->name],
            loggable: $entity,
        );

        $this->record = $entity;
        $this->writeTheValues($entity, (array) $this->payload('values', []));

        $avatar = $this->fileOf($this->payload('avatar_file'));

        if ($avatar instanceof File) {
            $entity->avatar_file_id = $avatar->id;
            $entity->save();

            new AttachFile(user: $this->user, file: $avatar)->execute();
        }

        if ($this->valuesNotWritten > 0) {
            $this->reason = AccountImportReasonEnum::PartlyWritten;
        }
    }

    private function writeAMonicaLifeEvent(): void
    {
        $participants = collect((array) $this->payload('participants', []))
            ->map(fn (mixed $uuid): ?Contact => $this->contactOf($uuid))
            ->filter()
            ->values();

        if ($participants->isEmpty()) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $duration = $this->payload('duration_in_minutes');
        $structure = (array) $this->payload('structure', []);
        $copy = $this->recordIdOf('life_event_type', ($structure['life_event_type'] ?? '').':'.($structure['version_number'] ?? ''));
        $version = $copy === null ? null : StructureVersion::query()->find($copy);
        $owner = $version?->structure->owner;

        $lifeEvent = new CreateLifeEvent(
            user: $this->user,
            vault: $this->vault(),
            contact: $participants->first(),
            lifeEventType: $owner instanceof LifeEventType ? $owner : $this->lifeEventTypeOf((string) $this->payload('type')),
            happenedOn: CarbonImmutable::parse((string) $this->payload('happened_on')),
            title: $this->shortText($this->payload('title')),
            narrative: $this->text($this->payload('narrative')),
            location: $this->shortText($this->payload('location')),
            durationInMinutes: is_int($duration) ? $duration : null,
            participants: $participants->slice(1)->values()->all(),
            createdFrom: LifeEventSourceEnum::Import,
        )->execute();

        $lifeEvent->forceFill([
            'ended_on' => $this->payload('ended_on'),
            'date_precision' => DatePrecisionEnum::tryFrom((string) $this->payload('date_precision')) ?? DatePrecisionEnum::Day,
            'happened_at_time' => $this->payload('happened_at_time'),
            'timezone' => $this->payload('timezone'),
            'latitude' => $this->payload('latitude'),
            'longitude' => $this->payload('longitude'),
            'is_shareable' => (bool) $this->payload('is_shareable', false),
            'narrative_is_shareable' => (bool) $this->payload('narrative_is_shareable', false),
        ])->saveQuietly();

        if ($version instanceof StructureVersion && $lifeEvent->structure_version_id !== $version->id) {
            StructureVersion::query()->whereKey($lifeEvent->structure_version_id)->where('record_count', '>', 0)->decrement('record_count');
            $version->increment('record_count');

            $lifeEvent->structure_version_id = $version->id;
            $lifeEvent->saveQuietly();
            $lifeEvent->setRelation('structureVersion', $version);
        }

        $this->record = $lifeEvent;
        $this->writeTheValues($lifeEvent, (array) $this->payload('values', []));

        if ($this->valuesNotWritten > 0) {
            $this->reason = AccountImportReasonEnum::PartlyWritten;
        }
    }

    private function writeTheRelatedValues(): void
    {
        $record = $this->recordOf($this->payload('record'));

        if ($record === null) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $write = fn () => $this->writeTheValues($record, (array) $this->payload('values', []));

        match (true) {
            $record instanceof Contact && $record->is_me => $this->writeIntoMe($record, $write),
            default => $write(),
        };

        if ($this->valuesNotWritten > 0) {
            $this->reason = AccountImportReasonEnum::PartlyWritten;
        }
    }

    private function writeAMonicaReminder(): void
    {
        $record = $this->recordOf($this->payload('record'));

        if ($record === null) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $cadence = ReminderCadenceEnum::tryFrom((string) $this->payload('cadence')) ?? ReminderCadenceEnum::Once;
        $interval = max(1, min(Reminder::MAXIMUM_INTERVAL, (int) $this->payload('frequency_interval', 1)));
        $timeOfDay = $this->text($this->payload('time_of_day')) ?? Reminder::DEFAULT_TIME;
        $timezone = $this->text($this->payload('timezone'));

        if ($this->payload('source') === ReminderSourceEnum::FieldValue->value) {
            $this->record = new CreateReminder(
                user: $this->user,
                fieldValue: $this->fieldValueOf($record, (array) $this->payload('value', [])),
                cadence: $cadence,
                timeOfDay: $timeOfDay,
                name: $this->shortText($this->payload('name')),
                frequencyInterval: $interval,
                timezone: $timezone,
            )->execute();

            return;
        }

        $startsOn = CarbonImmutable::parse((string) $this->payload('starts_on'))->startOfDay();

        if (! $record instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        if ($cadence === ReminderCadenceEnum::Once && $startsOn->isBefore(today())) {
            $this->leaveOut(AccountImportReasonEnum::OneTimeReminderInThePast);

            return;
        }

        $this->record = new CreateManualReminder(
            user: $this->user,
            record: $record,
            name: $this->shortText($this->payload('name')) ?? __('Reminder'),
            startsOn: $this->nextOccurrence($startsOn, $cadence, $interval)->format('Y-m-d'),
            cadence: $cadence,
            timeOfDay: $timeOfDay,
            frequencyInterval: $interval,
            timezone: $timezone,
        )->execute();
    }

    private function writeAMonicaRelationship(): void
    {
        $contact = $this->contactOf($this->payload('contact'));
        $otherContact = $this->contactOf($this->payload('other_contact'));

        if (! $contact instanceof Contact || ! $otherContact instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        try {
            $relationship = new CreateRelationship(
                user: $this->user,
                vault: $this->vault(),
                contact: $contact,
                relationshipType: $this->relationshipTypeOf((string) $this->payload('type')),
                otherContact: $otherContact,
                status: RelationshipStatusEnum::tryFrom((string) $this->payload('status')) ?? RelationshipStatusEnum::Current,
            )->execute();
        } catch (RelationshipIsAlreadyRecorded) {
            $this->leaveOut(AccountImportReasonEnum::RelationshipAlreadyRecorded);

            return;
        } catch (RelationshipWouldLoopParentage) {
            $this->leaveOut(AccountImportReasonEnum::ParentageLoop);

            return;
        }

        $relationship->forceFill([
            'started_on' => $this->payload('started_on'),
            'ended_on' => $this->payload('ended_on'),
            'notes' => $this->text($this->payload('notes')),
        ])->saveQuietly();

        $this->record = $relationship;
    }

    private function writeAMonicaGroup(): void
    {
        $name = $this->shortText($this->payload('name'));

        if ($name === null) {
            $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting);

            return;
        }

        $group = new CreateGroup(
            user: $this->user,
            vault: $this->vault(),
            name: $name,
            description: $this->text($this->payload('description')),
            startedOn: $this->exportedDate($this->payload('started_on')),
            startedPrecision: DatePrecisionEnum::tryFrom((string) $this->payload('started_precision')) ?? DatePrecisionEnum::Unknown,
            endedOn: $this->exportedDate($this->payload('ended_on')),
            endedPrecision: DatePrecisionEnum::tryFrom((string) $this->payload('ended_precision')) ?? DatePrecisionEnum::Unknown,
        )->execute();

        foreach ((array) $this->payload('members', []) as $member) {
            $member = is_array($member) ? $member : [];
            $contact = $this->contactOf($member['contact'] ?? null);

            if (! $contact instanceof Contact) {
                $this->valuesNotWritten++;

                continue;
            }

            try {
                new CreateGroupMembership(
                    user: $this->user,
                    vault: $this->vault(),
                    group: $group,
                    contact: $contact,
                    role: $this->shortText($member['role'] ?? null),
                    joinedOn: $this->exportedDate($member['joined_on'] ?? null),
                    joinedPrecision: DatePrecisionEnum::tryFrom((string) ($member['joined_precision'] ?? '')) ?? DatePrecisionEnum::Unknown,
                    leftOn: $this->exportedDate($member['left_on'] ?? null),
                    leftPrecision: DatePrecisionEnum::tryFrom((string) ($member['left_precision'] ?? '')) ?? DatePrecisionEnum::Unknown,
                )->execute();
            } catch (ContactIsAlreadyInTheGroup|MembershipEndsBeforeItStarts) {
                $this->valuesNotWritten++;
            }
        }

        $this->writeWhatTheGroupHolds($group);

        $this->record = $group;

        if ($this->valuesNotWritten > 0) {
            $this->reason = AccountImportReasonEnum::PartlyWritten;
        }
    }

    /**
     * The notes, files, activities and reminders an exported group carried. They travel inside the
     * group because the group is written after the life events and alongside the reminders.
     */
    private function writeWhatTheGroupHolds(Group $group): void
    {
        foreach ((array) $this->payload('notes', []) as $note) {
            $body = is_array($note) ? $this->text($note['body'] ?? null) : null;

            if ($body === null) {
                $this->valuesNotWritten++;

                continue;
            }

            $written = new CreateGroupNote(user: $this->user, vault: $this->vault(), group: $group, body: $body)->execute();
            $createdAt = $note['created_at'] ?? null;

            if (is_string($createdAt) && $createdAt !== '') {
                $written->forceFill(['created_at' => CarbonImmutable::parse($createdAt)])->saveQuietly();
            }
        }

        foreach ((array) $this->payload('files', []) as $uuid) {
            $file = $this->fileOf($uuid);

            if (! $file instanceof File) {
                $this->valuesNotWritten++;

                continue;
            }

            $attachment = GroupFile::query()->firstOrCreate(
                ['group_id' => $group->id, 'file_id' => $file->id],
                ['account_id' => $this->user->account_id, 'added_by_user_id' => $this->user->id],
            );

            if ($attachment->wasRecentlyCreated) {
                new AttachFile(user: $this->user, file: $file)->execute();
            }
        }

        $lifeEventIds = collect((array) $this->payload('life_events', []))
            ->map(fn (mixed $uuid): Contact|Entity|LifeEvent|null => $this->recordOf($uuid))
            ->filter(fn (mixed $record): bool => $record instanceof LifeEvent)
            ->map(fn (LifeEvent $lifeEvent): int => $lifeEvent->id)
            ->all();

        LifeEvent::query()->whereIn('id', $lifeEventIds)->update(['group_id' => $group->id]);

        foreach ((array) $this->payload('reminders', []) as $reminder) {
            $reminder = is_array($reminder) ? $reminder : [];
            $cadence = ReminderCadenceEnum::tryFrom((string) ($reminder['cadence'] ?? '')) ?? ReminderCadenceEnum::Once;
            $interval = max(1, min(Reminder::MAXIMUM_INTERVAL, (int) ($reminder['frequency_interval'] ?? 1)));
            $startsOn = CarbonImmutable::parse((string) ($reminder['starts_on'] ?? 'now'))->startOfDay();

            if ($cadence === ReminderCadenceEnum::Once && $startsOn->isBefore(today())) {
                continue;
            }

            try {
                new CreateManualReminder(
                    user: $this->user,
                    record: $group,
                    name: $this->shortText($reminder['name'] ?? null) ?? __('Reminder'),
                    startsOn: $this->nextOccurrence($startsOn, $cadence, $interval)->format('Y-m-d'),
                    cadence: $cadence,
                    timeOfDay: $this->text($reminder['time_of_day'] ?? null) ?? Reminder::DEFAULT_TIME,
                    frequencyInterval: $interval,
                    timezone: $this->text($reminder['timezone'] ?? null),
                )->execute();
            } catch (ReminderIsNotValid) {
                $this->valuesNotWritten++;
            }
        }
    }

    private function exportedDate(mixed $date): ?CarbonImmutable
    {
        return is_string($date) && $date !== '' ? CarbonImmutable::parse($date) : null;
    }

    /** @param array<mixed> $values */
    private function writeTheValues(Contact|Entity|LifeEvent $record, array $values): void
    {
        $fields = $this->fieldsByFilePath($record->structureVersion);
        $rows = [];
        $filled = [];

        foreach (collect($values)->filter(fn (mixed $value): bool => is_array($value))->sortBy(fn (array $value): int => (int) ($value['row'] ?? 0)) as $value) {
            $field = $fields[($value['section'] ?? '').'/'.($value['field'] ?? '')] ?? null;

            if (! $field instanceof Field) {
                $this->valuesNotWritten++;

                continue;
            }

            if ($this->meAlreadyHolds($record, $field)) {
                continue;
            }

            try {
                $repeater = $field->parent;
                $row = null;

                if ($repeater instanceof Field) {
                    $at = $repeater->id.'/'.($value['row'] ?? '');

                    if (! isset($rows[$at]) || isset($filled[$at][$field->id])) {
                        $rows[$at] = $this->rowOf($record, $repeater, $value['row'] ?? null);
                        $filled[$at] = [];
                    }

                    $row = $rows[$at];
                    $filled[$at][$field->id] = true;
                }

                $this->setAnExportedValue($record, $field, $row, $value);
            } catch (NotEnoughStorageLeft $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                Log::warning('Left out a value an import could not write.', [
                    'import' => $this->import->id,
                    'item' => $this->item->id,
                    'field' => $field->key,
                    'exception' => $exception->getMessage(),
                ]);

                $this->valuesNotWritten++;
            }
        }
    }

    /** @param array<string, mixed> $value */
    private function setAnExportedValue(Contact|Entity|LifeEvent $record, Field $field, ?FieldValueRow $row, array $value): void
    {
        $column = $field->type->valueColumn();
        $items = collect((array) ($value['list_items'] ?? []))
            ->map(fn (mixed $name): ?ValueListItem => $this->itemOf($field, $name) ?? $this->choiceAddedTo($field, $name))
            ->filter()
            ->values()
            ->all();
        $chosenItem = $column === 'value_list_item_id' ? ($items[0] ?? null) : null;

        new SetFieldValue(
            user: $this->user,
            valuable: $record,
            field: $field,
            row: $row,
            value: $value['value'] ?? null,
            valueListItem: $field->type->usesALabelList()
                ? $this->itemOf($field, $value['label'] ?? null)
                : $chosenItem,
            labelWording: $this->shortText($value['label_wording'] ?? null),
            valueListItems: $column === 'value_list_item_ids' ? $items : [],
            file: $this->fileOf($value['file'] ?? null),
            relatedContact: $this->contactOf($value['related_contact'] ?? null),
            relatedEntity: $this->entityOf($value['related_entity'] ?? null),
            yearIsUnknown: (bool) ($value['year_is_unknown'] ?? false),
            monthIsUnknown: (bool) ($value['month_is_unknown'] ?? false),
            writesToTheJournal: false,
        )->execute();
    }

    private function choiceAddedTo(Field $field, mixed $name): ?ValueListItem
    {
        if (! is_string($name) || $field->valueList === null) {
            return null;
        }

        try {
            $item = new FindOrCreateValueListItem(user: $this->user, valueList: $field->valueList, wording: $name)->execute();
        } catch (AuthorizationException|ValueListItemIsStopped) {
            return null;
        }

        if ($item->wasRecentlyCreated) {
            $this->keptPayload = [
                ...($this->keptPayload ?? []),
                'value_list_items' => [...($this->keptPayload['value_list_items'] ?? []), $item->id],
            ];
        }

        return $item;
    }

    private function rowOf(Contact|Entity|LifeEvent $record, Field $repeater, mixed $position): FieldValueRow
    {
        $row = new CreateFieldValueRow(user: $this->user, valuable: $record, field: $repeater, writesToTheJournal: false)->execute();

        if (is_int($position)) {
            $row->position = $position;
            $row->save();
        }

        return $row;
    }

    private function removeTheDefaultValues(Contact $contact): void
    {
        $defaults = FieldValue::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->get();

        foreach ($defaults as $default) {
            new DestroyFieldValue(user: $this->user, fieldValue: $default, writesToTheJournal: false)->execute();
        }
    }

    /** @return array<string, Field> */
    private function fieldsByFilePath(StructureVersion $version): array
    {
        $slugs = array_flip($this->sectionsOf($version));
        $byPath = [];

        foreach ($version->assembly()->with(['sectionVersion.section', 'sectionVersion.fields'])->get() as $row) {
            $section = $slugs[$row->section_version_id] ?? $row->sectionVersion->section->slug;

            foreach ($row->sectionVersion->fields as $field) {
                $byPath["{$section}/{$field->key}"] = $field;
            }
        }

        return $byPath;
    }

    /** @return array<string, int> */
    private function sectionsOf(StructureVersion $version): array
    {
        $payload = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('record_type', $version->getMorphClass())
            ->where('record_id', $version->getKey())
            ->value('payload');

        return is_array($payload) ? (array) ($payload['sections'] ?? []) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array{sections: array<string, int>, value_lists: list<int>, entity_types: list<int>}
     */
    private function whatTheCopyMade(StructureVersion $version, array $pages, int $valueLists, int $entityTypes): array
    {
        $slugs = collect($pages)->flatMap(fn (array $page): array => array_map(
            fn (array $row): string => (string) ($row['section']['slug'] ?? ''),
            $page['sections'],
        ));

        $placed = StructureVersionSection::query()
            ->where('structure_version_id', $version->id)
            ->orderBy('structure_version_page_id')
            ->orderBy('id')
            ->pluck('section_version_id');

        return [
            'sections' => $slugs->combine($placed)->all(),
            'value_lists' => ValueList::withTrashed()->where('account_id', $this->user->account_id)->where('id', '>', $valueLists)->pluck('id')->all(),
            'entity_types' => EntityType::withTrashed()->where('account_id', $this->user->account_id)->where('id', '>', $entityTypes)->pluck('id')->all(),
        ];
    }

    private function latestCopyOf(string $kind, string $owner): ?StructureVersion
    {
        $id = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('kind', $kind)
            ->whereRaw('substr(source_uuid, 1, ?) = ?', [mb_strlen($owner) + 1, "{$owner}:"])
            ->whereNotNull('record_id')
            ->orderByDesc('position')
            ->value('record_id');

        return $id === null ? null : StructureVersion::query()->find($id);
    }

    private function copyOf(mixed $structure): ?StructureVersion
    {
        if (! is_array($structure)) {
            return null;
        }

        $id = $this->recordIdOf('structure', ($structure['template'] ?? '').':'.($structure['version_number'] ?? ''));

        return $id === null ? null : StructureVersion::query()->find($id);
    }

    private function lifeEventTypeOf(string $type): LifeEventType
    {
        $own = $this->reference('life_event_types')[$type] ?? null;

        return LifeEventType::query()
            ->where('account_id', $this->user->account_id)
            ->where(is_string($own) ? 'name' : 'key', $own ?? $type)
            ->firstOrFail();
    }

    private function relationshipTypeOf(string $type): RelationshipType
    {
        $copy = $this->recordIdOf('relationship_type', $type);

        if ($copy !== null) {
            return RelationshipType::query()->findOrFail($copy);
        }

        $own = $this->reference('relationship_types')[$type] ?? null;

        return RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->where(is_string($own) ? 'name' : 'key', $own ?? $type)
            ->firstOrFail();
    }

    /** @param array<string, mixed> $value */
    private function fieldValueOf(Contact|Entity|LifeEvent $record, array $value): FieldValue
    {
        $field = $this->fieldsByFilePath($record->structureVersion)[($value['section'] ?? '').'/'.($value['field'] ?? '')] ?? null;
        $row = $value['row'] ?? null;

        return FieldValue::query()
            ->where('valuable_type', $record->getMorphClass())
            ->where('valuable_id', $record->id)
            ->where('field_id', $field?->id)
            ->when(is_int($row), fn ($query) => $query->whereHas('row', fn ($query) => $query->where('position', $row)))
            ->firstOrFail();
    }

    private function recordOf(mixed $sourceUuid): Contact|Entity|LifeEvent|null
    {
        if (! is_string($sourceUuid) || $sourceUuid === '') {
            return null;
        }

        if ($this->isWrittenIntoMe($sourceUuid)) {
            return $this->meOfTheVault();
        }

        $record = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->whereIn('kind', ['contact', 'entity', 'life_event'])
            ->where('source_uuid', $sourceUuid)
            ->whereNotNull('record_id')
            ->first()
            ?->record;

        return $record instanceof Contact || $record instanceof Entity || $record instanceof LifeEvent ? $record : null;
    }
}
