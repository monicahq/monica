<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\LifeEventSourceEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ValueListEnum;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\RelationshipIsAlreadyRecorded;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Exceptions\ValueListItemIsStopped;
use App\Helpers\MonicaFourVocabulary;
use App\Models\AccountImportItem;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\ValueListItem;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * How one item of a Monica 4 export is written, for the action writing an item of an import.
 */
trait WritesMonicaFourItems
{
    private function writeAMonicaFourItem(): void
    {
        match ($this->item->kind) {
            'life_event_type' => $this->writeANature(),
            'relationship_type' => $this->writeARelationshipType(),
            'file' => $this->writeAFile(),
            'contact' => $this->writeAContact(),
            'reminder' => $this->writeAReminder(),
            'life_event' => $this->writeALifeEvent(),
            'call' => $this->writeACall(),
            'conversation' => $this->writeAConversation(),
            'pet' => $this->writeAPet(),
            'activity' => $this->writeAnActivity(),
            'relationship' => $this->writeARelationship(),
            'journal_entry' => $this->writeAJournalEntry(),
            'me' => null,
            'archive' => $this->archive(),
            default => $this->leaveOut(AccountImportReasonEnum::RefusedWhileWriting),
        };
    }

    private function writeANature(): void
    {
        $name = MonicaFourVocabulary::natureName((string) $this->payload('key'));

        $isAlreadyThere = LifeEventType::query()
            ->where('account_id', $this->user->account_id)
            ->where('name', $name)
            ->exists();

        if ($isAlreadyThere) {
            return;
        }

        $wanted = $this->payload('category');
        $category = LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->where('key', MonicaFourVocabulary::natureCategory(is_string($wanted) ? $wanted : null)->value)
            ->firstOrFail();

        $this->record = new CreateLifeEventType(
            user: $this->user,
            lifeEventCategory: $category,
            name: $name,
        )->execute();
    }

    private function writeARelationshipType(): void
    {
        $relationship = (string) $this->payload('name');
        $name = MonicaFourVocabulary::roleName($relationship);

        $isAlreadyThere = RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->where('name', $name)
            ->exists();

        if ($isAlreadyThere) {
            return;
        }

        $category = RelationshipCategory::query()
            ->where('account_id', $this->user->account_id)
            ->where('key', MonicaFourVocabulary::ownRelationshipCategory($relationship)->value)
            ->firstOrFail();

        $this->record = new CreateRelationshipType(
            user: $this->user,
            relationshipCategory: $category,
            name: $name,
            roleA: $name,
            roleB: MonicaFourVocabulary::roleName(MonicaFourVocabulary::reverseOf($relationship)),
            isSymmetric: ! MonicaFourVocabulary::isDirected($relationship),
        )->execute();
    }

    private function writeAContact(): void
    {
        $properties = (array) $this->payload('properties', []);

        if ($this->joinsMe($this->item->source_uuid)) {
            $me = $this->meOfTheVault();
            $this->writeIntoMe($me, fn () => $this->writeTheDetailsOf($me, $properties));

            return;
        }

        $contact = new CreateContact(
            user: $this->user,
            vault: $this->vault(),
            firstName: $this->shortText($properties['first_name'] ?? null),
            middleName: $this->shortText($properties['middle_name'] ?? null),
            lastName: $this->shortText($properties['last_name'] ?? null),
            nickname: $this->shortText($properties['nickname'] ?? null),
            isPartial: (bool) ($properties['is_partial'] ?? false),
            isFavourite: (bool) ($properties['is_starred'] ?? false),
        )->execute();

        $this->record = $contact;
        $this->writeTheDetailsOf($contact, $properties);
    }

    /** @param array<string, mixed> $properties */
    private function writeTheDetailsOf(Contact $contact, array $properties): void
    {
        $fields = $this->fieldsOf($contact);

        $this->writeThePersonalDetails($contact, $fields, $properties);
        $this->writeTheWaysToReachThem($contact, $fields);
        $this->writeTheNotes($contact, $fields, $properties);
        $this->writeTheFiles($contact, $fields, $properties);

        if ($this->valuesNotWritten > 0) {
            $this->reason = AccountImportReasonEnum::PartlyWritten;
        }
    }

    /**
     * @param  array<string, Field>  $fields
     * @param  array<string, mixed>  $properties
     */
    private function writeThePersonalDetails(Contact $contact, array $fields, array $properties): void
    {
        $gender = $properties['gender'] ?? null;

        if (is_string($gender)) {
            $type = $this->reference('genders')[$gender] ?? null;
            $wanted = MonicaFourVocabulary::gender(is_string($type) ? $type : null);
            $item = $this->itemOf($fields['/gender'] ?? null, $wanted->value);

            if ($item === null || ! $this->setValue($contact, $fields, '/gender', valueListItem: $item)) {
                $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Gender'), 'value' => __($wanted->label())]));
            }
        }

        $birthdate = $properties['birthdate'] ?? null;

        $date = is_array($birthdate) && is_string($birthdate['date'] ?? null) ? $this->dateOf($birthdate['date']) : null;

        if ($date instanceof CarbonImmutable) {
            $isAgeBased = (bool) ($birthdate['is_age_based'] ?? false);
            $yearIsUnknown = (bool) ($birthdate['is_year_unknown'] ?? false);

            if ($isAgeBased) {
                $this->addANote($contact, $fields, __('Born around :year', ['year' => $date->year]));
            }

            if (! $isAgeBased && ! $this->setValue($contact, $fields, '/birthday', value: $date->format('Y-m-d'), yearIsUnknown: $yearIsUnknown)) {
                $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Birthday'), 'value' => $date->format($yearIsUnknown ? 'm-d' : 'Y-m-d')]));
            }
        }

        $description = $this->text($properties['description'] ?? null);

        if ($description !== null && ! $this->setValue($contact, $fields, '/description', value: $description)) {
            $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Description'), 'value' => $description]));
        }
    }

    /** @param array<string, Field> $fields */
    private function writeTheWaysToReachThem(Contact $contact, array $fields): void
    {
        $hasAWebsite = false;

        foreach ((array) $this->payload('contact_fields', []) as $contactField) {
            if (! is_array($contactField)) {
                continue;
            }

            $type = $contactField['type'] ?? null;
            $kind = is_string($type) ? (array) ($this->reference('contact_field_types')[$type] ?? []) : [];
            $data = $this->text($contactField['data'] ?? null);

            if ($data === null) {
                continue;
            }

            $isWritten = match ($kind['type'] ?? null) {
                'email' => $this->setValue($contact, $fields, 'emails/email', value: preg_replace('/^mailto:/i', '', $data)),
                'phone' => $this->setValue($contact, $fields, 'phones/phone', value: preg_replace('/^tel:/i', '', $data)),
                default => ! $hasAWebsite
                    && preg_match('#^https?://#i', $data) === 1
                    && ($hasAWebsite = $this->setValue($contact, $fields, '/website', value: $data)),
            };

            if (! $isWritten) {
                $isWritten = $this->addANote($contact, $fields, __(':label: :value', ['label' => $kind['name'] ?? __('Contact information'), 'value' => $data]));
            }

            if ($isWritten) {
                $this->countWritten('contact_fields');
            }
        }

        foreach ((array) $this->payload('addresses', []) as $address) {
            $wording = $this->shortText($address['name'] ?? null);
            $parts = [
                'po_box' => '',
                'extended' => '',
                'street' => (string) ($address['street'] ?? ''),
                'city' => (string) ($address['city'] ?? ''),
                'region' => (string) ($address['province'] ?? ''),
                'postal_code' => (string) ($address['postal_code'] ?? ''),
                'country' => (string) ($address['country'] ?? ''),
            ];

            $isWritten = $this->setValue(
                $contact,
                $fields,
                'addresses/address',
                value: $parts,
                valueListItem: $wording === null ? null : $this->itemOf($fields['addresses/address'] ?? null, ValueListItem::OPEN_KEY),
                labelWording: $wording,
            );

            if (! $isWritten) {
                $isWritten = $this->addANote($contact, $fields, implode(', ', array_filter($parts)));
            }

            if ($isWritten) {
                $this->countWritten('addresses');
            }
        }
    }

    /**
     * @param  array<string, Field>  $fields
     * @param  array<string, mixed>  $properties
     */
    private function writeTheNotes(Contact $contact, array $fields, array $properties): void
    {
        foreach ((array) $this->payload('notes', []) as $note) {
            $body = $this->text($note['body'] ?? null);

            if ($body !== null && $this->addANote($contact, $fields, $body)) {
                $this->countWritten('notes');
            }
        }

        $tags = is_array($properties['tags'] ?? null) ? $properties['tags'] : [];
        $labelled = [
            [__('Job'), $properties['job'] ?? null],
            [__('Company'), $properties['company'] ?? null],
            [__('Food preferences'), $properties['food_preferences'] ?? null],
        ];

        foreach ($labelled as [$label, $value]) {
            $value = $this->text($value);

            if ($value !== null) {
                $this->addANote($contact, $fields, __(':label: :value', ['label' => $label, 'value' => $value]));
            }
        }

        $leftOver = $this->writeTheTags($contact, $fields, $tags);
        $tagList = $this->text(implode(', ', $leftOver));

        if ($tagList !== null && $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Tags'), 'value' => $tagList]))) {
            $this->countWritten('tags', count($leftOver));
        }

        foreach ((array) $this->payload('gifts', []) as $gift) {
            $isWritten = $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Gift'), 'value' => $this->sentence([
                $gift['name'] ?? null,
                $gift['comment'] ?? null,
                $gift['url'] ?? null,
                $gift['amount'] ?? null,
                match ($gift['status'] ?? null) {
                    'idea' => __('An idea'),
                    'offered' => __('Offered'),
                    'received' => __('Received'),
                    default => null,
                },
            ])]));

            if ($isWritten) {
                $this->countWritten('gifts');
            }
        }

        foreach ((array) $this->payload('debts', []) as $debt) {
            $amount = trim(($debt['amount'] ?? '').' '.($debt['currency']['iso'] ?? ''));

            $isWritten = $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Debt'), 'value' => $this->sentence([
                ($debt['in_debt'] ?? false) ? __('You owe :amount', ['amount' => $amount]) : __('Owes you :amount', ['amount' => $amount]),
                ($debt['status'] ?? null) === 'complete' ? __('Settled') : null,
            ])]));

            if ($isWritten) {
                $this->countWritten('debts');
            }
        }

        foreach ((array) $this->payload('tasks', []) as $task) {
            $isWritten = $this->addANote($contact, $fields, __(':label: :value', ['label' => __('Task'), 'value' => $this->sentence([
                $task['title'] ?? null,
                $task['description'] ?? null,
                ($task['completed'] ?? false) ? __('Done') : null,
            ])]));

            if ($isWritten) {
                $this->countWritten('tasks');
            }
        }
    }

    /**
     * @param  array<string, Field>  $fields
     * @param  array<string, mixed>  $properties
     */
    private function writeTheFiles(Contact $contact, array $fields, array $properties): void
    {
        foreach ([['photos', 'photo/image'], ['documents', 'document/file']] as [$list, $path]) {
            foreach ((array) $this->payload($list, []) as $uuid) {
                $file = $this->fileOf((string) $uuid);

                if ($file instanceof File && ! $this->setValue($contact, $fields, $path, file: $file)) {
                    $this->leaveOutTheFile((string) $uuid, AccountImportReasonEnum::FileTypeNotKept);
                }
            }
        }

        $this->putTheAvatar($contact, $this->fileOf($properties['avatar_photo'] ?? null));
    }

    private function writeAReminder(): void
    {
        $contact = $this->contactOf($this->payload('contact'));

        if (! $contact instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $days = (int) $this->payload('stay_in_touch_days', 0);

        if ($days > 0) {
            $weeks = max(1, min(Reminder::MAXIMUM_INTERVAL, (int) round($days / 7)));

            $this->record = new CreateManualReminder(
                user: $this->user,
                record: $contact,
                name: __('Stay in touch with :name', ['name' => $contact->name]),
                startsOn: now()->addWeeks($weeks)->format('Y-m-d'),
                cadence: ReminderCadenceEnum::Weekly,
                timeOfDay: Reminder::DEFAULT_TIME,
                frequencyInterval: $weeks,
            )->execute();

            return;
        }

        $reminder = (array) $this->payload('reminder', []);

        $initial = CarbonImmutable::parse((string) ($reminder['initial_date'] ?? 'now'))->startOfDay();
        $interval = max(1, min(Reminder::MAXIMUM_INTERVAL, (int) ($reminder['frequency_number'] ?? 1)));
        $cadence = match ($reminder['frequency_type'] ?? null) {
            'week' => ReminderCadenceEnum::Weekly,
            'month' => ReminderCadenceEnum::Monthly,
            'year' => ReminderCadenceEnum::Yearly,
            default => ReminderCadenceEnum::Once,
        };

        if ($cadence === ReminderCadenceEnum::Once && $initial->isBefore(today())) {
            $this->leaveOut(AccountImportReasonEnum::OneTimeReminderInThePast);

            return;
        }

        $birthday = $this->birthdayOf($contact, $reminder);

        if ($birthday instanceof FieldValue && $birthday->reminder()->exists()) {
            $this->leaveOut(AccountImportReasonEnum::BirthdayAlreadyHasAReminder);

            return;
        }

        $rule = $birthday instanceof FieldValue
            ? new CreateReminder(
                user: $this->user,
                fieldValue: $birthday,
                cadence: ReminderCadenceEnum::Yearly,
                timeOfDay: Reminder::DEFAULT_TIME,
                name: $this->shortText($reminder['title'] ?? null),
            )->execute()
            : new CreateManualReminder(
                user: $this->user,
                record: $contact,
                name: $this->shortText($reminder['title'] ?? null) ?? __('Reminder'),
                startsOn: $this->nextOccurrence($initial, $cadence, $interval)->format('Y-m-d'),
                cadence: $cadence,
                timeOfDay: Reminder::DEFAULT_TIME,
                frequencyInterval: $interval,
            )->execute();

        $this->record = $rule;

        if (! (bool) ($reminder['inactive'] ?? false)) {
            return;
        }

        Reminder::query()->whereKey($rule->id)->update([
            'is_active' => false,
            'inactive_reason' => null,
            'deactivated_at' => now(),
        ]);

        new CancelReminderOccurrences(
            reminder: $rule,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleChanged,
            includePast: true,
        )->execute();
    }

    private function writeALifeEvent(): void
    {
        $contact = $this->contactOf($this->payload('contact'));

        if (! $contact instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $own = $this->payload('nature');
        $key = is_string($own) ? $own : (string) ($this->reference('life_event_types')[$this->payload('type')]['key'] ?? '');
        $shipped = MonicaFourVocabulary::nature($key);
        $nature = $shipped === null ? $this->ownNatureCalled(MonicaFourVocabulary::natureName($key)) : $this->natureKeyed($shipped->value);
        $participant = $this->contactOf($this->payload('participant'));
        $happenedOn = $this->dateOf($this->payload('happened_at'));

        if (! $happenedOn instanceof CarbonImmutable) {
            $this->leaveOut(AccountImportReasonEnum::DateNotValid);

            return;
        }

        $lifeEvent = new CreateLifeEvent(
            user: $this->user,
            vault: $this->vault(),
            contact: $contact,
            lifeEventType: $nature,
            happenedOn: $happenedOn,
            title: $this->shortText($this->payload('name')),
            narrative: $this->text($this->payload('note')),
            participants: $participant instanceof Contact && $participant->isNot($contact) ? [$participant] : [],
            createdFrom: LifeEventSourceEnum::Import,
        )->execute();

        $precision = DatePrecisionEnum::tryFrom((string) $this->payload('date_precision'));

        if ($precision instanceof DatePrecisionEnum) {
            $lifeEvent->forceFill(['date_precision' => $precision])->saveQuietly();
        }

        $this->record = $lifeEvent;
    }

    private function writeACall(): void
    {
        $this->writeOneOf('call', $this->payload('called_at'), null, $this->text($this->payload('content')));
    }

    private function writeAConversation(): void
    {
        $contact = $this->contactOf($this->payload('contact'));
        $lines = array_map(
            fn (array $message): string => __(':label: :value', [
                'label' => ($message['written_by_me'] ?? false) ? $this->user->name : $contact?->name,
                'value' => $message['content'] ?? '',
            ]),
            (array) $this->payload('messages', []),
        );

        $this->writeOneOf('message', $this->payload('happened_at'), null, implode("\n", $lines));
    }

    private function writeAnActivity(): void
    {
        $participants = collect((array) $this->payload('participants', []))
            ->map(fn (mixed $uuid): ?Contact => $this->contactOf($uuid))
            ->filter()
            ->values();

        if ($participants->isEmpty()) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $happenedOn = $this->dateOf($this->payload('happened_at'));

        if (! $happenedOn instanceof CarbonImmutable) {
            $this->leaveOut(AccountImportReasonEnum::DateNotValid);

            return;
        }

        $this->record = new CreateLifeEvent(
            user: $this->user,
            vault: $this->vault(),
            contact: $participants->first(),
            lifeEventType: $this->natureKeyed('time_together'),
            happenedOn: $happenedOn,
            title: $this->shortText($this->payload('summary')),
            narrative: $this->text($this->payload('description')),
            participants: $participants->slice(1)->values()->all(),
            createdFrom: LifeEventSourceEnum::Import,
        )->execute();
    }

    private function writeOneOf(string $nature, mixed $happenedAt, ?string $title, ?string $narrative): void
    {
        $contact = $this->contactOf($this->payload('contact'));

        if (! $contact instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $happenedOn = $this->dateOf($happenedAt);

        if (! $happenedOn instanceof CarbonImmutable) {
            $this->leaveOut(AccountImportReasonEnum::DateNotValid);

            return;
        }

        $this->record = new CreateLifeEvent(
            user: $this->user,
            vault: $this->vault(),
            contact: $contact,
            lifeEventType: $this->natureKeyed($nature),
            happenedOn: $happenedOn,
            title: $title,
            narrative: $narrative,
            createdFrom: LifeEventSourceEnum::Import,
        )->execute();
    }

    private function writeAPet(): void
    {
        $contact = $this->contactOf($this->payload('contact'));

        if (! $contact instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $entityType = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->where('slug', 'animals')
            ->firstOrFail();

        $animal = new CreateEntity(
            user: $this->user,
            vault: $this->vault(),
            entityType: $entityType,
            name: $this->shortText($this->payload('name')) ?? __('Pet'),
        )->execute();

        $this->record = $animal;

        $fields = $this->fieldsOf($animal);
        $category = $this->payload('category');
        $species = MonicaFourVocabulary::species(is_string($category) ? $category : null);

        if ($species !== null) {
            $this->setValue($animal, $fields, '/species', value: $species);
        }

        $this->setValue($animal, $fields, 'caregivers/caregiver_person', relatedContact: $contact);
    }

    private function writeARelationship(): void
    {
        $one = $this->contactOf($this->payload('contact_is'));
        $other = $this->contactOf($this->payload('of_contact'));

        if (! $one instanceof Contact || ! $other instanceof Contact) {
            $this->leaveOut(AccountImportReasonEnum::ContactNotInTheFile);

            return;
        }

        $name = (string) $this->payload('type');
        $shipped = MonicaFourVocabulary::relationshipType($name);
        $own = min($name, MonicaFourVocabulary::reverseOf($name));

        $type = RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->when(
                $shipped !== null,
                fn ($query) => $query->where('key', $shipped[0]->value),
                fn ($query) => $query->where('name', MonicaFourVocabulary::roleName($own)),
            )
            ->firstOrFail();

        try {
            $this->record = new CreateRelationship(
                user: $this->user,
                vault: $this->vault(),
                contact: $one,
                relationshipType: $type,
                otherContact: $other,
                swapped: $shipped !== null ? $shipped[1] : MonicaFourVocabulary::isDirected($name) && $name !== $own,
            )->execute();
        } catch (RelationshipIsAlreadyRecorded) {
            $this->leaveOut(AccountImportReasonEnum::RelationshipAlreadyRecorded);
        } catch (RelationshipWouldLoopParentage) {
            $this->leaveOut(AccountImportReasonEnum::ParentageLoop);
        }
    }

    private function writeAJournalEntry(): void
    {
        $me = Contact::query()
            ->where('vault_id', $this->vault()->id)
            ->where('is_me', true)
            ->firstOrFail();

        $date = $this->dateOf($this->payload('date'));

        if (! $date instanceof CarbonImmutable) {
            $this->leaveOut(AccountImportReasonEnum::DateNotValid);

            return;
        }

        if ($this->payload('type') === 'day') {
            $rating = __('The day of :date was rated :rate out of 3.', ['date' => $date->format('Y-m-d'), 'rate' => (int) $this->payload('rate')]);

            $this->addANote($me, $this->fieldsOf($me), $this->sentence([$rating, $this->payload('comment')]));
            $this->record = $this->lastRow;

            return;
        }

        $this->record = new CreateLifeEvent(
            user: $this->user,
            vault: $this->vault(),
            contact: $me,
            lifeEventType: $this->natureKeyed('reflection'),
            happenedOn: $date,
            title: $this->shortText($this->payload('title')),
            narrative: $this->text($this->payload('post')),
            createdFrom: LifeEventSourceEnum::Import,
        )->execute();
    }

    /** @param  array<string, Field>  $fields */
    private function setValue(
        Contact|Entity $record,
        array $fields,
        string $path,
        mixed $value = null,
        ?ValueListItem $valueListItem = null,
        ?string $labelWording = null,
        ?File $file = null,
        ?Contact $relatedContact = null,
        bool $yearIsUnknown = false,
    ): bool {
        $field = $fields[$path] ?? null;

        if (! $field instanceof Field) {
            return false;
        }

        if ($this->meAlreadyHolds($record, $field)) {
            return true;
        }

        try {
            DB::transaction(function () use ($record, $field, $value, $valueListItem, $labelWording, $file, $relatedContact, $yearIsUnknown): void {
                $this->lastRow = $field->parent_field_id === null
                    ? null
                    : new CreateFieldValueRow(user: $this->user, valuable: $record, field: $field->parent, writesToTheJournal: false)->execute();

                new SetFieldValue(
                    user: $this->user,
                    valuable: $record,
                    field: $field,
                    row: $this->lastRow,
                    value: $value,
                    valueListItem: $valueListItem,
                    labelWording: $labelWording,
                    file: $file,
                    relatedContact: $relatedContact,
                    yearIsUnknown: $yearIsUnknown,
                    writesToTheJournal: false,
                )->execute();
            });
        } catch (NotEnoughStorageLeft $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Left out a value an import could not write.', [
                'import' => $this->import->id,
                'item' => $this->item->id,
                'field' => $path,
                'exception' => $exception->getMessage(),
            ]);

            $this->lastRow = null;

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, Field>  $fields
     * @param  array<int, mixed>  $tags
     * @return list<string>
     */
    private function writeTheTags(Contact $contact, array $fields, array $tags): array
    {
        $wordings = array_values(array_filter(array_map(fn (mixed $tag): ?string => $this->shortText($tag), $tags)));

        $field = collect($fields)->first(fn (Field $field): bool => $field->parent_field_id === null
            && $field->type === FieldTypeEnum::Multiselect
            && $field->valueList?->key === ValueListEnum::ContactCategories->value);

        if (! $field instanceof Field || $wordings === []) {
            return $wordings;
        }

        if ($this->meAlreadyHolds($contact, $field)) {
            return [];
        }

        $chosen = [];
        $leftOver = [];

        foreach ($wordings as $wording) {
            try {
                $item = new FindOrCreateValueListItem(user: $this->user, valueList: $field->valueList, wording: $wording)->execute();
            } catch (AuthorizationException|ValueListItemIsStopped) {
                $leftOver[] = $wording;

                continue;
            }

            if ($item->wasRecentlyCreated) {
                $this->keptPayload = [
                    ...($this->keptPayload ?? []),
                    'value_list_items' => [...($this->keptPayload['value_list_items'] ?? []), $item->id],
                ];
            }

            $chosen[$item->id] = $item;
        }

        if ($chosen !== []) {
            new SetFieldValue(
                user: $this->user,
                valuable: $contact,
                field: $field,
                valueListItems: collect($chosen)->sortBy('position')->values()->all(),
                writesToTheJournal: false,
            )->execute();

            $this->countWritten('tags', count($chosen));
        }

        return $leftOver;
    }

    /** @param array<string, Field> $fields */
    private function addANote(Contact $contact, array $fields, string $text): bool
    {
        if (! $this->setValue($contact, $fields, 'notes/note', value: $text)) {
            $this->valuesNotWritten++;

            return false;
        }

        return true;
    }

    private function countWritten(string $kind, int $amount = 1): void
    {
        $this->written[$kind] = ($this->written[$kind] ?? 0) + $amount;
    }

    /** @return array<string, Field> */
    private function fieldsOf(Contact|Entity $record): array
    {
        $fields = $record->structureVersion
            ->sectionVersions()
            ->with('fields')
            ->get()
            ->flatMap(fn ($section) => $section->fields)
            ->keyBy('id');

        $byPath = [];

        foreach ($fields as $field) {
            $parent = $field->parent_field_id === null ? '' : ($fields[$field->parent_field_id]->key ?? '');
            $byPath[$parent.'/'.$field->key] = $field;
        }

        return $byPath;
    }

    /** @param  array<string, mixed>  $reminder */
    private function birthdayOf(Contact $contact, array $reminder): ?FieldValue
    {
        $birthdate = $this->payload('birthdate');

        if ((bool) ($reminder['delible'] ?? true) || ! is_string($birthdate)) {
            return null;
        }

        if (CarbonImmutable::parse($birthdate)->format('m-d') !== CarbonImmutable::parse((string) ($reminder['initial_date'] ?? ''))->format('m-d')) {
            return null;
        }

        return FieldValue::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_key', 'birthday')
            ->first();
    }

    private function natureKeyed(string $key): LifeEventType
    {
        return LifeEventType::query()
            ->where('account_id', $this->user->account_id)
            ->where('key', $key)
            ->firstOrFail();
    }

    private function ownNatureCalled(string $name): LifeEventType
    {
        return LifeEventType::query()
            ->where('account_id', $this->user->account_id)
            ->whereNull('key')
            ->where('name', $name)
            ->firstOrFail();
    }

    private function dateOf(mixed $value): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::parse((string) $value);
        } catch (InvalidFormatException) {
            return null;
        }

        if ($date->year < 1 || $date->year > 9999) {
            return null;
        }

        return $date;
    }

    /** @param list<mixed> $parts */
    private function sentence(array $parts): string
    {
        return implode('. ', array_filter(array_map($this->text(...), $parts)));
    }

    private function leaveOutTheFile(string $sourceUuid, AccountImportReasonEnum $reason): void
    {
        AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('kind', 'file')
            ->where('source_uuid', $sourceUuid)
            ->update(['outcome' => AccountImportOutcomeEnum::LeftOut->value, 'reason' => $reason->value]);
    }
}
