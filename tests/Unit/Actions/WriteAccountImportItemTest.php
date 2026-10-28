<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateContact;
use App\Actions\CreateDefaultValueLists;
use App\Actions\CreateFieldValueRow;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\CreateVault;
use App\Actions\PublishStructureVersion;
use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Actions\RemoveSectionFromStructureVersion;
use App\Actions\SetFieldValue;
use App\Actions\StartAccountImport;
use App\Actions\WriteAccountExport;
use App\Actions\WriteAccountImportItem;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportStatusEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\LogSourceEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedRelationshipTypes;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\LifeEvent;
use App\Models\Log;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Notifications\AccountImportEnded;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WriteAccountImportItemTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();

        $this->michael = $this->accountHolder('Michael', 'Scott');
    }

    #[Test]
    public function it_writes_every_contact_of_the_file_into_a_new_vault_and_ends(): void
    {
        $import = $this->imported();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->status);
        $this->assertSame(['Cece', 'Dwight', 'Jim', 'Pam'], Contact::query()->where('vault_id', $import->vault_id)->where('is_me', false)->orderBy('first_name')->pluck('first_name')->all());
        $this->assertSame(0, $import->items()->whereNull('outcome')->count());
        Storage::disk('local')->assertMissing($import->directory());
        Notification::assertSentTo($this->michael, AccountImportEnded::class, fn (AccountImportEnded $notification): bool => ! $notification->rolledBack);
    }

    #[Test]
    public function it_fills_me_with_the_contact_the_file_marks_as_its_owner_without_overwriting_me(): void
    {
        $import = $this->imported();
        $me = $this->me($import);

        $this->assertNull($me->middle_name);
        $this->assertDatabaseHas('field_values', ['valuable_id' => $me->id, 'field_key' => 'email', 'value_string' => 'michael@dundermifflin.test']);
        $this->assertSame(AccountImportOutcomeEnum::MergedIntoMe, $import->items()->where('kind', 'contact')->where('source_uuid', '10000000-0000-4000-8000-000000000001')->first()->outcome);
        $this->assertSame(4, Contact::withTrashed()->where('vault_id', $import->vault_id)->where('is_me', false)->count());
    }

    #[Test]
    public function it_writes_into_me_only_what_me_does_not_hold_yet(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $me = Contact::query()->where('vault_id', $scranton->id)->where('is_me', true)->firstOrFail();
        $this->setTheBirthday($me, '1965-03-15');
        $this->addAnEmail($me, 'michael@dundermifflin.test');

        $this->imported(vault: $scranton);

        $this->assertSame('1965-03-15', $this->birthdayOf($me));
        $this->assertSame(1, FieldValue::query()->where('valuable_id', $me->id)->where('valuable_type', $me->getMorphClass())->where('value_string', 'michael@dundermifflin.test')->count());
        $this->assertContains('Job: Regional Manager', $this->notesOf($me));
        $this->assertFalse(Contact::withTrashed()->where('vault_id', $scranton->id)->where('first_name', 'Michael')->where('is_me', false)->exists());
    }

    #[Test]
    public function it_keeps_a_birthday_without_its_year_and_writes_an_age_as_a_note(): void
    {
        $import = $this->imported();

        $dwight = $this->contactCalled($import, 'Dwight');
        $jim = $this->contactCalled($import, 'Jim');

        $this->assertDatabaseHas('field_values', ['valuable_id' => $dwight->id, 'field_key' => 'birthday', 'year_is_unknown' => true]);
        $this->assertDatabaseMissing('field_values', ['valuable_id' => $jim->id, 'field_key' => 'birthday']);
        $this->assertContains('Born around 1978', $this->notesOf($jim));
    }

    #[Test]
    public function it_keeps_what_monica_has_no_field_for_as_notes(): void
    {
        $import = $this->imported();
        $notes = implode("\n", $this->notesOf($this->me($import)));

        $this->assertStringContainsString('Job: Regional Manager', $notes);
        $this->assertStringContainsString('Tags: boss, improv', $notes);
        $this->assertStringContainsString('Gift: Oven mitt', $notes);
        $this->assertStringContainsString('You owe 20.00 USD', $notes);
        $this->assertStringContainsString('Task: Plan the fun run', $notes);
        $this->assertStringContainsString('Hosts the Dundies every year.', $notes);
    }

    #[Test]
    public function it_writes_a_link_as_the_website_and_a_handle_as_a_note(): void
    {
        $import = $this->imported();
        $dwight = $this->contactCalled($import, 'Dwight');

        $this->assertDatabaseHas('field_values', ['valuable_id' => $dwight->id, 'field_key' => 'website', 'value_string' => 'https://facebook.example.test/schrutefarms']);
        $this->assertContains('Telegram: beetfarmer', $this->notesOf($dwight));
    }

    #[Test]
    public function it_writes_the_reminders_the_way_monica_keeps_them(): void
    {
        $import = $this->imported();
        $dwight = $this->contactCalled($import, 'Dwight');

        $this->assertDatabaseHas('reminders', ['name' => 'Wish happy birthday to Michael', 'cadence' => ReminderCadenceEnum::Yearly->value]);
        $this->assertNotNull(Reminder::query()->where('name', 'Wish happy birthday to Michael')->value('field_value_id'));
        $this->assertDatabaseHas('reminders', ['remindable_id' => $dwight->id, 'cadence' => ReminderCadenceEnum::Weekly->value, 'frequency_interval' => 4]);

        $past = $import->items()->where('kind', 'reminder')->where('outcome', AccountImportOutcomeEnum::LeftOut)->first();

        $this->assertSame(AccountImportReasonEnum::OneTimeReminderInThePast->value, $past->reason);
    }

    #[Test]
    public function it_writes_a_reminder_switched_off_in_monica_four_and_switches_it_off(): void
    {
        $import = $this->started();
        $item = $import->items()->where('kind', 'reminder')->get()->firstOrFail(fn ($item): bool => ($item->payload['reminder']['title'] ?? null) === 'Wish happy birthday to Michael');
        $item->update(['payload' => [...$item->payload, 'reminder' => [...$item->payload['reminder'], 'inactive' => true]]]);

        $this->writeEveryItem($import);

        $reminder = Reminder::query()->where('name', 'Wish happy birthday to Michael')->firstOrFail();

        $this->assertSame(AccountImportOutcomeEnum::Imported, $item->refresh()->outcome);
        $this->assertFalse($reminder->is_active);
        $this->assertNotNull($reminder->deactivated_at);
        $this->assertFalse($reminder->occurrences()->whereNull('cancelled_at')->exists());
    }

    #[Test]
    public function it_writes_a_gender_and_a_birthday_with_no_field_to_hold_them_as_notes_naming_them(): void
    {
        Field::query()->where('account_id', $this->michael->account_id)->where('key', 'gender')->update(['key' => 'gender_elsewhere']);
        Field::query()->where('account_id', $this->michael->account_id)->where('key', 'birthday')->update(['key' => 'birthday_elsewhere']);

        $import = $this->imported();
        $notes = $this->notesOf($this->contactCalled($import, 'Dwight'));

        $this->assertContains('Gender: Man', $notes);
        $this->assertContains('Birthday: 01-20', $notes);
    }

    #[Test]
    public function it_counts_the_values_it_writes_inside_a_contact(): void
    {
        $import = $this->started();
        $dwight = $this->itemOfDwight($import);
        $dwight->update(['payload' => [...$dwight->payload, 'notes' => [['body' => 'Bears.'], ['body' => 'Beets.']], 'gifts' => [['name' => 'A bobblehead']]]]);

        $this->writeEveryItem($import);

        $this->assertSame(2, $dwight->refresh()->payload['written']['notes']);
        $this->assertSame(1, $dwight->payload['written']['gifts']);
    }

    #[Test]
    public function it_does_not_count_a_value_it_could_not_write_inside_a_contact(): void
    {
        Field::query()->where('account_id', $this->michael->account_id)->where('key', 'note')->update(['key' => 'note_elsewhere']);
        $import = $this->started();
        $dwight = $this->itemOfDwight($import);
        $dwight->update(['payload' => [...$dwight->payload, 'notes' => [['body' => 'Bears.']], 'gifts' => [['name' => 'A bobblehead']]]]);

        $this->writeEveryItem($import);

        $this->assertArrayNotHasKey('notes', $dwight->refresh()->payload['written'] ?? []);
        $this->assertArrayNotHasKey('gifts', $dwight->payload['written'] ?? []);
    }

    #[Test]
    public function it_goes_on_past_a_value_of_the_wrong_type(): void
    {
        $import = $this->started();
        $pet = $import->items()->where('kind', 'pet')->firstOrFail();
        $pet->update(['payload' => [...$pet->payload, 'category' => 7]]);
        $lifeEvent = $import->items()->where('kind', 'life_event')->firstOrFail();
        $lifeEvent->update(['payload' => [...$lifeEvent->payload, 'nature' => null, 'type' => ['first_kiss']]]);

        $this->writeEveryItem($import);

        $this->assertSame(AccountImportOutcomeEnum::Imported, $pet->refresh()->outcome);
        $this->assertTrue(Entity::query()->where('vault_id', $import->vault_id)->where('name', "Mose's goat")->exists());
        $this->assertSame(AccountImportOutcomeEnum::LeftOut, $lifeEvent->refresh()->outcome);
        $this->assertSame(AccountImportReasonEnum::RefusedWhileWriting->value, $lifeEvent->reason);
        $this->assertSame(0, $import->items()->whereNull('outcome')->count());
    }

    #[Test]
    public function it_logs_a_value_it_leaves_out_and_goes_on(): void
    {
        LogFacade::spy();
        $import = $this->started();
        $stamfordFile = File::factory()->create();
        $import->items()->where('kind', 'file')->where('source_uuid', '30000000-0000-4000-8000-000000000001')->firstOrFail()->update([
            'outcome' => AccountImportOutcomeEnum::Imported,
            'record_type' => $stamfordFile->getMorphClass(),
            'record_id' => $stamfordFile->id,
        ]);
        $dwight = $import->items()->where('kind', 'contact')->get()->firstOrFail(fn (AccountImportItem $item): bool => ($item->payload['properties']['first_name'] ?? null) === 'Dwight');
        $dwight->update(['payload' => [...$dwight->payload, 'photos' => ['30000000-0000-4000-8000-000000000001']]]);

        $this->writeEveryItem($import);

        $this->assertSame(AccountImportOutcomeEnum::Imported, $dwight->refresh()->outcome);
        LogFacade::shouldHaveReceived('warning')->with('Left out a value an import could not write.', Mockery::on(fn (array $context): bool => $context['item'] === $dwight->id && $context['field'] === 'photo/image'));
    }

    #[Test]
    public function it_keeps_when_each_record_was_created_in_the_file(): void
    {
        $import = $this->started();

        foreach (['pet', 'call', 'reminder', 'relationship'] as $kind) {
            $item = $import->items()->where('kind', $kind)->firstOrFail();
            $item->update(['payload' => [...$item->payload, 'created_at' => '2009-03-26T09:00:00Z']]);
        }

        $this->writeEveryItem($import);

        foreach (['pet', 'call', 'reminder', 'relationship'] as $kind) {
            $createdAt = $import->items()->where('kind', $kind)->whereNotNull('record_id')->firstOrFail()->record?->getAttribute('created_at');

            $this->assertInstanceOf(CarbonInterface::class, $createdAt);
            $this->assertSame('2009-03-26', $createdAt->format('Y-m-d'), $kind);
        }
    }

    #[Test]
    public function it_keeps_a_life_event_as_vague_as_the_file_has_it(): void
    {
        $import = $this->started();
        $item = $import->items()->where('kind', 'life_event')->firstOrFail();
        $item->update(['payload' => [...$item->payload, 'date_precision' => 'year']]);

        $this->writeEveryItem($import);

        $lifeEvent = $item->refresh()->record;

        $this->assertInstanceOf(LifeEvent::class, $lifeEvent);
        $this->assertSame(DatePrecisionEnum::Year, $lifeEvent->date_precision);
    }

    #[Test]
    public function it_writes_life_events_calls_and_conversations_of_the_matching_nature(): void
    {
        $import = $this->imported();

        $natures = LifeEvent::query()->where('vault_id', $import->vault_id)->with('lifeEventType')->get()->map(fn (LifeEvent $lifeEvent): string => $lifeEvent->lifeEventType->key ?? $lifeEvent->lifeEventType->name)->sort()->values()->all();

        $this->assertSame(['First kiss', 'call', 'message', 'reflection', 'time_together'], $natures);
        $this->assertSame(5, LifeEvent::query()->where('vault_id', $import->vault_id)->where('created_from', 'import')->count());
        $this->assertStringContainsString('Bears, beets, Battlestar Galactica.', (string) LifeEvent::query()->where('vault_id', $import->vault_id)->whereHas('lifeEventType', fn ($query) => $query->where('key', 'message'))->value('narrative'));
    }

    #[Test]
    public function it_writes_an_activity_with_everybody_who_took_part(): void
    {
        $import = $this->imported();

        $cruise = LifeEvent::query()->where('vault_id', $import->vault_id)->where('title', 'Booze cruise')->firstOrFail();

        $this->assertSame(['Jim', 'Pam'], $cruise->participants()->with('participant')->get()->map(fn ($participant): string => $participant->participant->first_name)->sort()->values()->all());
    }

    #[Test]
    public function it_writes_a_pet_as_an_animal_cared_for_by_its_owner(): void
    {
        $import = $this->imported();
        $goat = Entity::query()->where('vault_id', $import->vault_id)->where('name', "Mose's goat")->firstOrFail();

        $this->assertDatabaseHas('field_values', ['valuable_id' => $goat->id, 'field_key' => 'caregiver_person', 'related_contact_id' => $this->contactCalled($import, 'Dwight')->id]);
    }

    #[Test]
    public function it_writes_each_relationship_once_and_in_its_direction(): void
    {
        $import = $this->imported();
        $jim = $this->contactCalled($import, 'Jim');
        $cece = $this->contactCalled($import, 'Cece');
        $godparent = RelationshipType::query()->where('account_id', $this->michael->account_id)->where('name', 'Godparent')->firstOrFail();

        $this->assertSame(2, Relationship::query()->where('vault_id', $import->vault_id)->count());
        $this->assertDatabaseHas('relationships', ['contact_a_id' => $jim->id, 'contact_b_id' => $cece->id]);
        $this->assertDatabaseHas('relationships', ['relationship_type_id' => $godparent->id, 'contact_a_id' => $this->me($import)->id, 'contact_b_id' => $cece->id]);
        $this->assertSame('Godchild', $godparent->role_b);
    }

    #[Test]
    public function it_archives_an_inactive_contact_and_keeps_when_each_was_created(): void
    {
        $import = $this->imported();

        $this->assertTrue($this->contactCalled($import, 'Pam')->is_archived);
        $this->assertSame('2017-04-01', $this->contactCalled($import, 'Dwight')->created_at->format('Y-m-d'));
        $this->assertTrue($this->contactCalled($import, 'Cece')->is_partial);
    }

    #[Test]
    public function it_writes_the_journal_with_me(): void
    {
        $import = $this->imported();
        $me = $this->me($import);

        $this->assertDatabaseHas('life_events', ['vault_id' => $import->vault_id, 'title' => 'Dinner party']);
        $this->assertStringContainsString('rated 2 out of 3', implode("\n", $this->notesOf($me)));
    }

    #[Test]
    public function it_logs_what_it_writes_as_coming_from_the_import(): void
    {
        $import = $this->imported();

        $this->assertTrue(Log::query()->where('source', LogSourceEnum::Import->value)->where('source_detail', $import->uuid)->exists());
        $this->assertNull(request()->attributes->get('log_source'));
    }

    #[Test]
    public function it_leaves_the_files_out_when_this_instance_keeps_none(): void
    {
        Feature::define('file-upload', false);

        $import = $this->imported();

        $this->assertSame(2, $import->items()->where('kind', 'file')->where('reason', AccountImportReasonEnum::FilesNotOffered->value)->count());
    }

    #[Test]
    public function it_copies_each_version_of_a_template_in_use_and_puts_every_record_on_the_copy_of_its_version(): void
    {
        Queue::fake();

        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $dwight = new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Dwight', lastName: 'Schrute')->execute();
        $first = $dwight->structureVersion;
        $this->setTheBirthday($dwight, '1970-01-20');

        $draft = new CreateStructureVersionDraft(user: $this->michael, structureVersion: $first)->execute();
        $notes = $draft->assembly()->whereHas('sectionVersion.section', fn ($query) => $query->where('slug', 'notes'))->firstOrFail();
        new RemoveSectionFromStructureVersion(user: $this->michael, row: $notes)->execute();
        $second = new PublishStructureVersion(user: $this->michael, structureVersion: $draft, changeNote: 'The notes moved to the annex.')->execute();

        $jim = new CreateContact(user: $this->michael, vault: $scranton, structureVersion: $second, firstName: 'Jim', lastName: 'Halpert')->execute();
        $this->setTheBirthday($jim, '1978-10-01');

        $pam = $this->accountHolder('Pam', 'Beesly');
        $import = $this->readMonicaImport($pam, $this->exported($scranton));
        new StartAccountImport(user: $pam, import: $import, vaultName: 'Scranton')->execute();
        $this->writeEveryItem($import);

        $copy = Template::query()->where('account_id', $pam->account_id)->where('is_system', false)->sole();
        $versions = StructureVersion::query()->where('structure_id', $copy->structure?->id)->orderBy('version_number')->get();
        $pamsDwight = Contact::query()->where('vault_id', $import->refresh()->vault_id)->where('first_name', 'Dwight')->firstOrFail();
        $pamsJim = Contact::query()->where('vault_id', $import->vault_id)->where('first_name', 'Jim')->firstOrFail();

        $this->assertSame([1, 2], $versions->pluck('version_number')->all());
        $this->assertSame([VersionStatusEnum::Published, VersionStatusEnum::Published], $versions->pluck('status')->all());
        $this->assertSame($versions[0]->id, $versions[1]->previous_version_id);
        $this->assertSame($versions[1]->id, $copy->structure?->published_version_id);
        $this->assertSame($first->assembly()->count(), $versions[0]->assembly()->count());
        $this->assertSame($second->assembly()->count(), $versions[1]->assembly()->count());
        $this->assertSame($versions[0]->id, $pamsDwight->structure_version_id);
        $this->assertSame($versions[1]->id, $pamsJim->structure_version_id);
        $this->assertSame('1970-01-20', $this->birthdayOf($pamsDwight));
        $this->assertSame('1978-10-01', $this->birthdayOf($pamsJim));

        foreach ([$pamsDwight, $pamsJim] as $contact) {
            Queue::assertPushedOn('low', DetectDuplicateContacts::class, fn (DetectDuplicateContacts $job): bool => $job->uniqueId() === $contact->getMorphClass().':'.$contact->id);
        }
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

    private function setTheBirthday(Contact $contact, string $date): void
    {
        $birthday = Field::query()
            ->whereIn('section_version_id', $contact->structureVersion->assembly()->select('section_version_id'))
            ->where('key', 'birthday')
            ->firstOrFail();

        new SetFieldValue(user: $this->michael, valuable: $contact, field: $birthday, value: $date)->execute();
    }

    private function addAnEmail(Contact $contact, string $email): void
    {
        $field = Field::query()
            ->whereIn('section_version_id', $contact->structureVersion->assembly()->select('section_version_id'))
            ->where('key', 'email')
            ->firstOrFail();

        $row = new CreateFieldValueRow(user: $this->michael, valuable: $contact, field: $field->parent)->execute();

        new SetFieldValue(user: $this->michael, valuable: $contact, field: $field, row: $row, value: $email)->execute();
    }

    private function birthdayOf(Contact $contact): ?string
    {
        return FieldValue::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_key', 'birthday')
            ->firstOrFail()
            ->value_date?->format('Y-m-d');
    }

    private function exported(Vault $vault): string
    {
        $export = AccountExport::factory()->create(['user_id' => $this->michael->id, 'vault_ids' => [$vault->id]]);

        new WriteAccountExport(export: $export, path: $export->path)->execute();

        return (string) Storage::disk('local')->get($export->path);
    }

    private function readMonicaImport(User $user, string $text): AccountImport
    {
        $import = AccountImport::factory()->create(['user_id' => $user->id]);
        Storage::disk('local')->put($import->path, $text);

        new ReadMonicaFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);

        return $import;
    }

    private function writeEveryItem(AccountImport $import): void
    {
        $import->refresh();

        while (($item = $import->items()->whereNull('outcome')->first()) !== null) {
            new WriteAccountImportItem(user: $import->user, import: $import, item: $item)->execute();
        }
    }

    private function imported(?Vault $vault = null): AccountImport
    {
        $import = AccountImport::factory()->create(['user_id' => $this->michael->id]);
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);

        new StartAccountImport(
            user: $this->michael,
            import: $import,
            vault: $vault,
            vaultName: $vault === null ? 'Dunder Mifflin' : null,
            meSourceUuid: '10000000-0000-4000-8000-000000000001',
        )->execute();

        return $import->refresh();
    }

    private function started(): AccountImport
    {
        Queue::fake();

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

        return $import->refresh();
    }

    private function itemOfDwight(AccountImport $import): AccountImportItem
    {
        return $import->items()->where('kind', 'contact')->get()->firstOrFail(fn (AccountImportItem $item): bool => ($item->payload['properties']['first_name'] ?? null) === 'Dwight');
    }

    private function me(AccountImport $import): Contact
    {
        return Contact::query()->where('vault_id', $import->vault_id)->where('is_me', true)->firstOrFail();
    }

    private function contactCalled(AccountImport $import, string $firstName): Contact
    {
        return Contact::query()->where('vault_id', $import->vault_id)->where('first_name', $firstName)->firstOrFail();
    }

    /** @return list<string> */
    private function notesOf(Contact $contact): array
    {
        return FieldValue::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_key', 'note')
            ->pluck('value_text')
            ->all();
    }
}
