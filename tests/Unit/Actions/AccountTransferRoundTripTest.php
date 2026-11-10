<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddGroupFile;
use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateDefaultValueLists;
use App\Actions\CreateLifeEvent;
use App\Actions\CreateManualReminder;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Actions\SetFieldValue;
use App\Actions\StartAccountImport;
use App\Actions\WriteAccountExport;
use App\Enums\AccountImportStatusEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderCadenceEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedRelationshipTypes;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\Relationship;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTransferRoundTripTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    private Vault $dunderMifflin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();

        $this->michael = $this->accountHolder('Michael', 'Scott');
        $this->dunderMifflin = $this->vaultImportedFromMonicaFour();
        $this->recordTheChannelOfTheCall();
    }

    #[Test]
    public function it_brings_a_vault_into_another_account_without_a_difference_on_any_record(): void
    {
        $import = $this->importedByPam();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertSame([], $import->items()->whereNotNull('reason')->pluck('reason', 'kind')->all());
        $this->assertNotEmpty(Entity::query()->where('vault_id', $this->dunderMifflin->id)->get());
        $this->assertNotEmpty(Reminder::query()->where('vault_id', $this->dunderMifflin->id)->get());
        $this->assertSame($this->described($this->dunderMifflin), $this->described(Vault::query()->findOrFail($import->vault_id)));
    }

    #[Test]
    public function it_brings_the_value_of_a_field_somebody_added_to_a_built_in_life_event_type(): void
    {
        $call = LifeEventType::query()->where('account_id', $this->michael->account_id)->where('key', 'call')->firstOrFail();
        $topic = $this->fieldAddedTo($call, 'Topic');
        $this->lifeEventWith($call, 'The Dundies call', $topic, 'Who wins a Dundie');

        $import = $this->importedByPam();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertSame([], $import->items()->whereNotNull('reason')->pluck('reason', 'kind')->all());
        $this->assertSame($this->described($this->dunderMifflin), $this->described(Vault::query()->findOrFail($import->vault_id)));
    }

    #[Test]
    public function it_brings_the_values_of_a_life_event_written_on_an_earlier_version_of_its_type(): void
    {
        $harvest = $this->lifeEventTypeAsking($this->michael, 'Beet harvest', ['Variety' => FieldTypeEnum::Text]);
        $this->lifeEventWith($harvest, 'The first harvest', $harvest->structure->publishedVersion->inputFields()->firstOrFail(), 'Detroit Dark Red');

        $weight = $this->fieldAddedTo($harvest, 'Weight');
        $this->lifeEventWith($harvest, 'The second harvest', $weight, 'Forty pounds');

        $import = $this->importedByPam();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertSame([], $import->items()->whereNotNull('reason')->pluck('reason', 'kind')->all());
        $this->assertSame($this->described($this->dunderMifflin), $this->described(Vault::query()->findOrFail($import->vault_id)));
    }

    #[Test]
    public function it_brings_the_groups_with_their_members_and_the_periods_they_belonged(): void
    {
        $contacts = Contact::query()->where('vault_id', $this->dunderMifflin->id)->where('is_me', false)->orderBy('id')->take(2)->get();
        $branch = Group::factory()->create([
            'vault_id' => $this->dunderMifflin->id,
            'name' => 'Scranton branch',
            'description' => 'Dunder Mifflin paper company',
            'started_on' => '1998-01-01',
            'started_precision' => DatePrecisionEnum::Year,
        ]);
        GroupMember::factory()->create([
            'group_id' => $branch->id,
            'contact_id' => $contacts[0]->id,
            'role' => 'Salesman',
            'joined_on' => '2001-04-01',
            'joined_precision' => DatePrecisionEnum::Month,
        ]);
        GroupMember::factory()->former()->create(['group_id' => $branch->id, 'contact_id' => $contacts[1]->id]);
        GroupMember::factory()->create(['group_id' => $branch->id, 'contact_id' => $contacts[1]->id]);

        $import = $this->importedByPam();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertSame([], $import->items()->whereNotNull('reason')->pluck('reason', 'kind')->all());
        $this->assertSame($this->described($this->dunderMifflin), $this->described(Vault::query()->findOrFail($import->vault_id)));
        $this->assertSame(3, GroupMember::query()->whereHas('group', fn ($query) => $query->where('vault_id', $import->vault_id))->count());
    }

    #[Test]
    public function it_brings_what_a_group_holds_with_it(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $contact = Contact::query()->where('vault_id', $this->dunderMifflin->id)->where('is_me', false)->orderBy('id')->firstOrFail();
        $committee = Group::factory()->create(['vault_id' => $this->dunderMifflin->id, 'name' => 'Party planning committee']);
        GroupMember::factory()->create(['group_id' => $committee->id, 'contact_id' => $contact->id]);
        GroupNote::factory()->create(['group_id' => $committee->id, 'body' => 'Angela keeps the budget.', 'created_at' => '2026-11-02 10:00:00']);
        $party = new CreateLifeEvent(
            user: $this->michael,
            vault: $this->dunderMifflin,
            contact: $contact,
            lifeEventType: $this->lifeEventTypeAsking($this->michael, 'Party'),
            happenedOn: CarbonImmutable::parse('2026-10-31'),
            title: 'Halloween party',
            group: $committee,
        )->execute();
        new CreateManualReminder(
            user: $this->michael,
            record: $committee,
            name: 'Plan the Christmas party',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
        new AddGroupFile(user: $this->michael, vault: $this->dunderMifflin, group: $committee, upload: UploadedFile::fake()->createWithContent('budget.pdf', 'Party budget: 400 dollars'))->execute();

        $import = $this->importedByPam();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertSame([], $import->items()->whereNotNull('reason')->pluck('reason', 'kind')->all());
        $copy = Group::query()->where('vault_id', $import->vault_id)->where('name', 'Party planning committee')->firstOrFail();
        $this->assertSame(['Angela keeps the budget.'], $copy->notes()->pluck('body')->all());
        $this->assertSame('2026-11-02', $copy->notes()->firstOrFail()->created_at->toDateString());
        $this->assertSame(['Halloween party'], $copy->lifeEvents()->pluck('title')->all());
        $this->assertSame(['Plan the Christmas party'], $copy->reminders()->pluck('name')->all());
        $this->assertSame(['budget.pdf'], $copy->files()->with('file')->get()->map(fn (GroupFile $attachment): string => $attachment->file->original_name)->all());
        $this->assertNotSame($party->id, $copy->lifeEvents()->firstOrFail()->id);
    }

    private function importedByPam(): AccountImport
    {
        $pam = $this->accountHolder('Pam', 'Beesly');

        $import = AccountImport::factory()->create(['user_id' => $pam->id]);
        Storage::disk('local')->put($import->path, $this->exported());

        new ReadMonicaFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);

        new StartAccountImport(user: $pam, import: $import, vaultName: 'Dunder Mifflin', meSourceUuid: $import->me_source_uuid)->execute();

        return $import->refresh();
    }

    private function fieldAddedTo(LifeEventType $lifeEventType, string $label): Field
    {
        $draft = new CreateStructureVersionDraft(
            user: $this->michael,
            structureVersion: $lifeEventType->refresh()->structure->publishedVersion,
        )->execute();

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $this->michael->account_id,
            'section_id' => Section::factory()->create(['account_id' => $this->michael->account_id])->id,
        ]);

        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $label]);

        new AddSectionToStructureVersion(user: $this->michael, structureVersion: $draft, sectionVersion: $sectionVersion)->execute();
        new PublishStructureVersion(user: $this->michael, structureVersion: $draft, changeNote: "{$label} added.")->execute();

        return $field;
    }

    private function lifeEventWith(LifeEventType $lifeEventType, string $title, Field $field, string $value): void
    {
        $lifeEvent = new CreateLifeEvent(
            user: $this->michael,
            vault: $this->dunderMifflin,
            contact: Contact::query()->where('vault_id', $this->dunderMifflin->id)->where('is_me', false)->orderBy('id')->firstOrFail(),
            lifeEventType: $lifeEventType->refresh(),
            happenedOn: CarbonImmutable::parse('2007-05-10'),
            title: $title,
        )->execute();

        new SetFieldValue(user: $this->michael, valuable: $lifeEvent, field: $field, value: $value)->execute();
    }

    /** @return array<string, mixed> */
    private function described(Vault $vault): array
    {
        return [
            'contacts' => Contact::query()
                ->where('vault_id', $vault->id)
                ->with('nameRepresentations')
                ->get()
                ->mapWithKeys(fn (Contact $contact): array => [$this->nameOf($contact) => [
                    'names' => $contact->is_me ? [] : $contact->nameRepresentations
                        ->map(fn (ContactNameRepresentation $name): array => [$name->kind->value, $name->given_name, $name->family_name, $name->id === $contact->display_name_representation_id])
                        ->all(),
                    'nickname' => $contact->is_me ? null : $contact->nickname,
                    'flags' => [$contact->is_favourite, $contact->is_partial, $contact->is_archived],
                    'created_at' => $contact->is_me ? null : $contact->created_at->format('Y-m-d H:i:s'),
                    'avatar' => $contact->avatar_file_id !== null,
                    'values' => $this->valuesOf($contact),
                ]])
                ->sortKeys()
                ->all(),
            'entities' => Entity::query()
                ->where('vault_id', $vault->id)
                ->get()
                ->mapWithKeys(fn (Entity $entity): array => [$entity->name => [$entity->entityType->slug, $entity->is_archived, $this->valuesOf($entity)]])
                ->sortKeys()
                ->all(),
            'relationships' => Relationship::query()
                ->where('vault_id', $vault->id)
                ->get()
                ->map(fn (Relationship $relationship): array => [
                    $relationship->relationshipType->key ?? $relationship->relationshipType->name,
                    $this->nameOf($relationship->contactA),
                    $this->nameOf($relationship->contactB),
                    $relationship->status->value,
                ])
                ->sort()
                ->values()
                ->all(),
            'life_events' => LifeEvent::query()
                ->where('vault_id', $vault->id)
                ->get()
                ->map(fn (LifeEvent $lifeEvent): array => [
                    $lifeEvent->lifeEventType->name,
                    $lifeEvent->happened_on->format('Y-m-d'),
                    $lifeEvent->date_precision->value,
                    $lifeEvent->title,
                    $lifeEvent->narrative,
                    $lifeEvent->participants->map(fn (LifeEventParticipant $participant): string => $this->nameOf($participant->participant))->sort()->values()->all(),
                    $this->valuesOf($lifeEvent),
                    $lifeEvent->structureVersion->inputFields()->pluck('key')->sort()->values()->all(),
                ])
                ->sort()
                ->values()
                ->all(),
            'groups' => Group::query()
                ->where('vault_id', $vault->id)
                ->with('members.contact')
                ->get()
                ->map(fn (Group $group): array => [
                    $group->name,
                    $group->description,
                    $group->started_on?->format('Y-m-d'),
                    $group->started_precision->value,
                    $group->ended_on?->format('Y-m-d'),
                    $group->ended_precision->value,
                    $group->members
                        ->map(fn (GroupMember $membership): array => [
                            $this->nameOf($membership->contact),
                            $membership->role,
                            $membership->joined_on?->format('Y-m-d'),
                            $membership->joined_precision->value,
                            $membership->left_on?->format('Y-m-d'),
                            $membership->left_precision->value,
                        ])
                        ->sort()
                        ->values()
                        ->all(),
                ])
                ->sort()
                ->values()
                ->all(),
            'reminders' => Reminder::query()
                ->where('vault_id', $vault->id)
                ->get()
                ->map(fn (Reminder $reminder): array => [
                    $this->nameOf($reminder->remindable),
                    $reminder->name,
                    $reminder->source->value,
                    $reminder->cadence->value,
                    $reminder->frequency_interval,
                    $reminder->starts_on?->format('Y-m-d'),
                    $reminder->fieldValue?->field_key,
                    $reminder->is_active,
                ])
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /** @return list<array<int, mixed>> */
    private function valuesOf(Contact|Entity|LifeEvent $record): array
    {
        return FieldValue::query()
            ->where('valuable_type', $record->getMorphClass())
            ->where('valuable_id', $record->id)
            ->where('is_detached', false)
            ->with(['field.parent', 'row', 'valueListItem', 'file', 'relatedContact', 'relatedEntity'])
            ->get()
            ->map(fn (FieldValue $value): array => [
                ($value->field->parent->key ?? '').'/'.$value->field_key,
                $value->row?->position,
                $value->value_string ?? $value->value_text ?? $value->value_number ?? $value->value_date?->format('Y-m-d') ?? $value->value_json ?? $value->value_boolean,
                $value->valueListItem->key ?? $value->valueListItem?->getRawOriginal('label'),
                ValueListItem::query()->whereIn('id', $value->value_list_item_ids ?? [])->pluck('key')->sort()->values()->all(),
                $value->label_wording,
                $value->year_is_unknown,
                $value->file?->checksum,
                $this->nameOf($value->relatedContact ?? $value->relatedEntity),
            ])
            ->sort()
            ->values()
            ->all();
    }

    private function nameOf(mixed $record): string
    {
        return match (true) {
            $record instanceof Contact && $record->is_me => 'Me',
            $record instanceof Contact, $record instanceof Entity => (string) $record->name,
            $record instanceof LifeEvent => (string) $record->title,
            default => '',
        };
    }

    private function recordTheChannelOfTheCall(): void
    {
        $call = LifeEvent::query()
            ->where('vault_id', $this->dunderMifflin->id)
            ->whereHas('lifeEventType', fn ($query) => $query->where('key', 'call'))
            ->firstOrFail();

        $channel = Field::query()
            ->whereIn('section_version_id', $call->structureVersion->assembly()->select('section_version_id'))
            ->where('key', 'channel')
            ->firstOrFail();

        new SetFieldValue(
            user: $this->michael,
            valuable: $call,
            field: $channel,
            valueListItem: ValueListItem::query()->where('value_list_id', $channel->value_list_id)->where('key', 'video')->firstOrFail(),
        )->execute();
    }

    private function exported(): string
    {
        $export = AccountExport::factory()->create(['user_id' => $this->michael->id, 'vault_ids' => [$this->dunderMifflin->id]]);

        new WriteAccountExport(export: $export, path: $export->path)->execute();

        return (string) Storage::disk('local')->get($export->path);
    }

    private function vaultImportedFromMonicaFour(): Vault
    {
        $import = AccountImport::factory()->create(['user_id' => $this->michael->id]);
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);

        new StartAccountImport(
            user: $this->michael,
            import: $import,
            vaultName: 'Dunder Mifflin',
            meSourceUuid: '10000000-0000-4000-8000-000000000001',
        )->execute();

        return Vault::query()->findOrFail($import->refresh()->vault_id);
    }

    private function accountHolder(string $firstName, string $lastName): User
    {
        $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);

        new CreateDefaultValueLists(user: $user)->execute();
        new SeedLifeEventCategories(user: $user)->handle();
        new CreateDefaultTemplate(user: $user)->handle();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        new CreateDefaultLifeEventTypes(user: $user)->handle();
        new SeedRelationshipTypes(user: $user)->handle();

        return $user;
    }
}
