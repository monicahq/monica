<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateContact;
use App\Actions\CreateDefaultValueLists;
use App\Actions\CreateEntity;
use App\Actions\CreateLifeEvent;
use App\Actions\CreateRelationship;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\CreateVault;
use App\Actions\PublishStructureVersion;
use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Actions\RollBackAccountImport;
use App\Actions\StartAccountImport;
use App\Actions\WriteAccountExport;
use App\Enums\AccountImportStatusEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedRelationshipTypes;
use App\Models\Account;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\Log;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Notifications\AccountImportEnded;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RollBackAccountImportTest extends TestCase
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
    public function it_removes_a_vault_the_import_created_and_the_storage_its_files_took(): void
    {
        $import = $this->imported(vault: null);
        $vaultId = $import->vault_id;

        new RollBackAccountImport(import: $import)->execute();

        $this->assertNull(Vault::withTrashed()->find($vaultId));
        $this->assertSame(0, File::withTrashed()->where('account_id', $this->michael->account_id)->count());
        $this->assertSame(0, Account::query()->findOrFail($this->michael->account_id)->storage_used_in_bytes);
        $this->assertFalse(RelationshipType::query()->where('account_id', $this->michael->account_id)->where('name', 'Godparent')->exists());
        $this->assertSame(AccountImportStatusEnum::RolledBack, $import->refresh()->status);
        Notification::assertSentTo($this->michael, AccountImportEnded::class, fn (AccountImportEnded $notification): bool => $notification->rolledBack);
    }

    #[Test]
    public function it_removes_the_categories_the_import_added_and_keeps_the_ones_that_were_there(): void
    {
        $categories = ValueList::query()
            ->where('account_id', $this->michael->account_id)
            ->where('key', ValueListEnum::ContactCategories->value)
            ->sole();
        $improv = ValueListItem::factory()->create(['account_id' => $this->michael->account_id, 'value_list_id' => $categories->id, 'label' => 'Improv']);
        $import = $this->imported(vault: null);
        $boss = ValueListItem::query()->where('value_list_id', $categories->id)->where('label', 'boss')->sole();

        new RollBackAccountImport(import: $import)->execute();

        $this->assertNull(ValueListItem::withTrashed()->find($boss->id));
        $this->assertModelExists($improv);
        $this->assertSame(0, $improv->refresh()->usage_count);
    }

    #[Test]
    public function it_leaves_a_vault_that_was_there_and_its_me_as_they_were(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $me = Contact::query()->where('vault_id', $scranton->id)->where('is_me', true)->firstOrFail();
        $valuesOfMe = FieldValue::query()->where('valuable_id', $me->id)->where('valuable_type', $me->getMorphClass())->count();

        $import = $this->imported(vault: $scranton);

        new RollBackAccountImport(import: $import)->execute();

        $this->assertSame([$me->id], Contact::withTrashed()->where('vault_id', $scranton->id)->pluck('id')->all());
        $this->assertSame($valuesOfMe, FieldValue::query()->where('valuable_id', $me->id)->where('valuable_type', $me->getMorphClass())->count());
        $this->assertSame(0, Relationship::withTrashed()->where('vault_id', $scranton->id)->count());
        $this->assertSame(0, LifeEvent::withTrashed()->where('vault_id', $scranton->id)->count());
        $this->assertSame(0, Entity::withTrashed()->where('vault_id', $scranton->id)->count());
        $this->assertSame(0, File::withTrashed()->where('vault_id', $scranton->id)->count());
        $this->assertSame(0, Account::query()->findOrFail($this->michael->account_id)->storage_used_in_bytes);
        $this->assertSame(1, $scranton->refresh()->contact_count);
    }

    #[Test]
    public function it_removes_every_vault_and_every_template_copy_an_import_of_several_vaults_made(): void
    {
        config(['monica.self_hosted' => true]);

        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $stamford = new CreateVault(user: $this->michael, name: 'Stamford')->execute();
        new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Jim', lastName: 'Halpert')->execute();
        new CreateContact(user: $this->michael, vault: $stamford, firstName: 'Andy', lastName: 'Bernard')->execute();
        new CreateEntity(
            user: $this->michael,
            vault: $scranton,
            entityType: EntityType::query()->where('account_id', $this->michael->account_id)->where('slug', 'animals')->firstOrFail(),
            name: "Mose's goat",
        )->execute();

        $pam = $this->accountHolder('Pam', 'Beesly');
        $before = $this->whatTheAccountHolds($pam);

        $import = AccountImport::factory()->create(['user_id' => $pam->id]);
        Storage::disk('local')->put($import->path, $this->exported([$scranton, $stamford]));
        new ReadMonicaFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);
        new StartAccountImport(user: $pam, import: $import)->execute();

        $import->refresh();

        $this->assertCount(2, $import->created_vault_ids);
        $this->assertSame(2, Template::query()->where('account_id', $pam->account_id)->where('is_system', false)->count());

        new RollBackAccountImport(import: $import)->execute();

        $this->assertSame(0, Vault::withTrashed()->whereIn('id', $import->created_vault_ids)->count());
        $this->assertSame($before, $this->whatTheAccountHolds($pam));
        $this->assertSame(AccountImportStatusEnum::RolledBack, $import->refresh()->status);
    }

    #[Test]
    public function it_removes_the_groups_an_import_wrote_into_a_vault_that_was_there(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $jim = new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Jim', lastName: 'Halpert')->execute();
        $branch = Group::factory()->create(['vault_id' => $scranton->id, 'name' => 'Scranton branch']);
        GroupMember::factory()->create(['group_id' => $branch->id, 'contact_id' => $jim->id]);

        $pam = $this->accountHolder('Pam', 'Beesly');
        $stamford = new CreateVault(user: $pam, name: 'Stamford')->execute();
        $import = AccountImport::factory()->create(['user_id' => $pam->id]);
        Storage::disk('local')->put($import->path, $this->exported([$scranton]));
        new ReadMonicaFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);
        new StartAccountImport(user: $pam, import: $import, vault: $stamford)->execute();

        $this->assertSame(1, Group::query()->where('vault_id', $stamford->id)->count());

        new RollBackAccountImport(import: $import->refresh())->execute();

        $this->assertSame(0, Group::withTrashed()->where('vault_id', $stamford->id)->count());
    }

    #[Test]
    public function it_removes_every_life_event_type_copy_and_every_version_an_import_made(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $jim = new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Jim', lastName: 'Halpert')->execute();

        $call = LifeEventType::query()->where('account_id', $this->michael->account_id)->where('key', 'call')->firstOrFail();
        $this->fieldAddedTo($call);
        $this->lifeEventOf($jim, $call);

        $harvest = $this->lifeEventTypeAsking($this->michael, 'Beet harvest', ['Variety' => FieldTypeEnum::Text]);
        $this->lifeEventOf($jim, $harvest);
        $this->fieldAddedTo($harvest);
        $this->lifeEventOf($jim, $harvest);

        $pam = $this->accountHolder('Pam', 'Beesly');
        $before = $this->whatTheAccountHolds($pam);

        $import = AccountImport::factory()->create(['user_id' => $pam->id]);
        Storage::disk('local')->put($import->path, $this->exported([$scranton]));
        new ReadMonicaFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);
        new StartAccountImport(user: $pam, import: $import, vaultName: 'Scranton')->execute();

        $this->assertSame($before['life_event_types'] + 2, LifeEventType::withTrashed()->where('account_id', $pam->account_id)->count());

        new RollBackAccountImport(import: $import->refresh())->execute();

        $this->assertSame($before, $this->whatTheAccountHolds($pam));
    }

    #[Test]
    public function it_gives_back_every_count_the_import_raised_when_it_removes_the_vault_it_created(): void
    {
        $before = $this->countsOf($this->michael);

        $import = $this->imported(vault: null);

        new RollBackAccountImport(import: $import)->execute();

        $this->assertSame($before, $this->countsOf($this->michael));
    }

    #[Test]
    public function it_gives_back_every_count_the_import_raised_in_a_vault_that_was_there(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $before = $this->countsOf($this->michael);

        $import = $this->imported(vault: $scranton);

        new RollBackAccountImport(import: $import)->execute();

        $this->assertSame($before, $this->countsOf($this->michael));
    }

    #[Test]
    public function it_removes_the_types_the_import_created_for_good_without_writing_to_the_journal(): void
    {
        $before = Structure::query()->where('account_id', $this->michael->account_id)->count();
        $import = $this->imported(vault: null);
        $logged = Log::query()->where('account_id', $this->michael->account_id)->count();

        new RollBackAccountImport(import: $import)->execute();

        $this->assertFalse(RelationshipType::withTrashed()->where('account_id', $this->michael->account_id)->where('name', 'Godparent')->exists());
        $this->assertSame(0, LifeEventType::withTrashed()->whereIn('id', $this->createdBy($import, LifeEventType::class))->count());
        $this->assertSame($before, Structure::query()->where('account_id', $this->michael->account_id)->count());
        $this->assertSame($logged, Log::query()->where('account_id', $this->michael->account_id)->count());
    }

    #[Test]
    public function it_keeps_a_type_the_import_created_that_somebody_has_used_since(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $dwight = new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Dwight', lastName: 'Schrute')->execute();
        $jim = new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Jim', lastName: 'Halpert')->execute();
        $import = $this->imported(vault: $scranton);
        $godparent = RelationshipType::query()->where('account_id', $this->michael->account_id)->where('name', 'Godparent')->firstOrFail();

        new CreateRelationship(user: $this->michael, vault: $scranton, contact: $dwight, relationshipType: $godparent, otherContact: $jim)->execute();

        new RollBackAccountImport(import: $import)->execute();

        $this->assertModelExists($godparent);
        $this->assertSame(1, Relationship::query()->where('relationship_type_id', $godparent->id)->count());
        $this->assertSame(AccountImportStatusEnum::RolledBack, $import->refresh()->status);
    }

    #[Test]
    public function it_removes_the_bytes_of_the_files_it_removes(): void
    {
        $import = $this->imported(vault: null);
        $paths = File::query()->where('account_id', $this->michael->account_id)->pluck('path');

        new RollBackAccountImport(import: $import)->execute();

        $this->assertNotEmpty($paths);
        $paths->each(fn (string $path) => Storage::disk('local')->assertMissing($path));
    }

    #[Test]
    public function it_deletes_the_working_files_of_the_import(): void
    {
        $import = $this->imported(vault: null);
        Storage::disk('local')->put("{$import->directory()}/files/photo", 'Dwight');

        new RollBackAccountImport(import: $import)->execute();

        Storage::disk('local')->assertMissing($import->directory());
    }

    #[Test]
    public function it_does_nothing_to_an_import_already_rolled_back(): void
    {
        $import = $this->imported(vault: null);
        new RollBackAccountImport(import: $import)->execute();
        Notification::fake();

        new RollBackAccountImport(import: $import->refresh())->execute();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_looks_for_duplicates_in_a_vault_that_was_there_once_the_import_is_rolled_back(): void
    {
        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $import = $this->imported(vault: $scranton);
        Queue::fake();

        new RollBackAccountImport(import: $import)->execute();

        Queue::assertPushed(DetectDuplicateContacts::class, 1);
        Queue::assertPushedOn('low', DetectDuplicateContacts::class, fn (DetectDuplicateContacts $job): bool => $job->uniqueId() === $scranton->getMorphClass().':'.$scranton->id);
    }

    #[Test]
    public function it_looks_for_no_duplicate_in_the_vault_it_created_and_removed(): void
    {
        $import = $this->imported(vault: null);
        Queue::fake();

        new RollBackAccountImport(import: $import)->execute();

        Queue::assertNotPushed(DetectDuplicateContacts::class);
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

    /** @return array<string, int> */
    private function countsOf(User $user): array
    {
        return [
            'record_count' => (int) StructureVersion::query()->where('account_id', $user->account_id)->sum('record_count'),
            'entity_count' => (int) EntityType::withTrashed()->where('account_id', $user->account_id)->sum('entity_count'),
            'life_event_count' => (int) Contact::withTrashed()->where('account_id', $user->account_id)->sum('life_event_count'),
            'contact_count' => (int) Vault::withTrashed()->where('account_id', $user->account_id)->sum('contact_count'),
            'usage_count' => (int) ValueListItem::query()->where('account_id', $user->account_id)->sum('usage_count'),
            'storage_used_in_bytes' => Account::query()->findOrFail($user->account_id)->storage_used_in_bytes,
        ];
    }

    /** @return list<int> */
    private function createdBy(AccountImport $import, string $class): array
    {
        return $import->items()->where('record_type', new $class()->getMorphClass())->pluck('record_id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    /** @return array<string, int> */
    private function whatTheAccountHolds(User $user): array
    {
        return [
            'vaults' => Vault::withTrashed()->where('account_id', $user->account_id)->count(),
            'contacts' => Contact::withTrashed()->where('account_id', $user->account_id)->count(),
            'entities' => Entity::withTrashed()->where('account_id', $user->account_id)->count(),
            'templates' => Template::withTrashed()->where('account_id', $user->account_id)->count(),
            'life_event_types' => LifeEventType::withTrashed()->where('account_id', $user->account_id)->count(),
            'structures' => Structure::query()->where('account_id', $user->account_id)->count(),
            'structure_versions' => StructureVersion::query()->where('account_id', $user->account_id)->count(),
            'sections' => Section::withTrashed()->where('account_id', $user->account_id)->count(),
            'value_lists' => ValueList::withTrashed()->where('account_id', $user->account_id)->count(),
            'entity_types' => EntityType::withTrashed()->where('account_id', $user->account_id)->count(),
        ];
    }

    private function fieldAddedTo(LifeEventType $lifeEventType): void
    {
        $draft = new CreateStructureVersionDraft(
            user: $this->michael,
            structureVersion: $lifeEventType->refresh()->structure->publishedVersion,
        )->execute();

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $this->michael->account_id,
            'section_id' => Section::factory()->create(['account_id' => $this->michael->account_id])->id,
        ]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id]);

        new AddSectionToStructureVersion(user: $this->michael, structureVersion: $draft, sectionVersion: $sectionVersion)->execute();
        new PublishStructureVersion(user: $this->michael, structureVersion: $draft, changeNote: 'A field added.')->execute();
    }

    private function lifeEventOf(Contact $contact, LifeEventType $lifeEventType): void
    {
        new CreateLifeEvent(
            user: $this->michael,
            vault: $contact->vault,
            contact: $contact,
            lifeEventType: $lifeEventType->refresh(),
            happenedOn: CarbonImmutable::parse('2007-05-10'),
            title: 'Beach games',
        )->execute();
    }

    /** @param list<Vault> $vaults */
    private function exported(array $vaults): string
    {
        $export = AccountExport::factory()->create([
            'user_id' => $this->michael->id,
            'vault_ids' => array_map(fn (Vault $vault): int => $vault->id, $vaults),
        ]);

        new WriteAccountExport(export: $export, path: $export->path)->execute();

        return (string) Storage::disk('local')->get($export->path);
    }

    private function imported(?Vault $vault): AccountImport
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
}
