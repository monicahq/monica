<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateContact;
use App\Actions\CreateVault;
use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Actions\StartAccountImport;
use App\Actions\WriteAccountExport;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Enums\DefaultTemplateEnum;
use App\Exceptions\ImportFileIsNotReadable;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedRelationshipTypes;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadMonicaFileTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();

        $this->michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        new SeedLifeEventCategories(user: $this->michael)->handle();
        new CreateDefaultTemplate(user: $this->michael)->handle();
        new CreateDefaultTemplate(user: $this->michael, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        new CreateDefaultLifeEventTypes(user: $this->michael)->handle();
        new SeedRelationshipTypes(user: $this->michael)->handle();
    }

    #[Test]
    public function it_reads_every_kind_the_export_holds_into_items(): void
    {
        $text = $this->exported([$this->dunderMifflin()]);
        $file = $this->decoded($text);
        $vault = $file['account']['vaults'][0];

        $import = $this->read($text);
        $kinds = $import->items()->get()->countBy('kind')->all();

        $this->assertSame(1, $kinds['vault']);
        $this->assertSame(count($file['account']['structures']), $kinds['structure']);
        $this->assertSame(count($file['account']['life_event_types']), $kinds['life_event_type']);
        $this->assertSame(count($file['account']['relationship_types']), $kinds['relationship_type']);
        $this->assertSame(count($vault['contacts']), $kinds['contact']);
        $this->assertSame(count($vault['entities']), $kinds['entity']);
        $this->assertSame(count($vault['relationships']), $kinds['relationship']);
        $this->assertSame(count($vault['life_events']), $kinds['life_event']);
        $this->assertSame(count($vault['reminders']), $kinds['reminder']);
        $this->assertSame(count($vault['files']), $kinds['file']);
        $this->assertSame(1, $kinds['archive']);
        $this->assertArrayNotHasKey('me', $kinds);
        $this->assertSame(['found' => count($vault['contacts']), 'left_out' => 0], $import->summary['contacts']);
        $this->assertSame([$vault['uuid']], $import->items()->reorder()->where('kind', 'contact')->distinct()->pluck('vault_source_uuid')->all());
        $this->assertSame(AccountImportSourceEnum::Monica, $import->source_format);
    }

    #[Test]
    public function it_offers_the_contact_the_file_marks_as_me_when_the_file_holds_one_vault(): void
    {
        $vault = $this->dunderMifflin();

        $import = $this->read($this->exported([$vault]));

        $this->assertSame(Contact::query()->where('vault_id', $vault->id)->where('is_me', true)->value('uuid'), $import->me_source_uuid);
    }

    #[Test]
    public function it_reads_each_vault_of_a_file_of_several_into_a_new_vault_with_its_own_me(): void
    {
        config(['monica.self_hosted' => true]);

        $scranton = new CreateVault(user: $this->michael, name: 'Scranton')->execute();
        $stamford = new CreateVault(user: $this->michael, name: 'Stamford')->execute();
        new CreateContact(user: $this->michael, vault: $scranton, firstName: 'Jim', lastName: 'Halpert')->execute();
        new CreateContact(user: $this->michael, vault: $stamford, firstName: 'Andy', lastName: 'Bernard')->execute();

        $import = $this->read($this->exported([$scranton, $stamford]));

        $this->assertSame(['Scranton', 'Stamford'], $import->items()->where('kind', 'vault')->get()->pluck('payload.name')->all());
        $this->assertNull($import->me_source_uuid);

        foreach ([$scranton, $stamford] as $vault) {
            $me = Contact::query()->where('vault_id', $vault->id)->where('is_me', true)->firstOrFail();

            $this->assertTrue($import->items()->where('kind', 'me')->where('source_uuid', $me->uuid)->where('vault_source_uuid', $vault->uuid)->exists());
            $this->assertSame(2, $import->items()->where('kind', 'contact')->where('vault_source_uuid', $vault->uuid)->count());
        }
    }

    #[Test]
    public function it_decodes_the_embedded_files_into_the_folder_of_the_import(): void
    {
        $text = $this->exported([$this->dunderMifflin()]);
        $files = $this->decoded($text)['account']['vaults'][0]['files'];

        $import = $this->read($text);

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $path = (string) $import->items()->where('kind', 'file')->where('source_uuid', $file['uuid'])->sole()->payload['path'];

            $this->assertStringStartsWith($import->directory().'/files/', $path);
            $this->assertSame(base64_decode((string) $file['data'], true), Storage::disk('local')->get($path));
        }
    }

    #[Test]
    public function it_keeps_the_date_every_record_was_created_in_the_source(): void
    {
        $import = $this->read($this->exported([$this->dunderMifflin()]));

        $items = $import->items()->whereIn('kind', ['contact', 'entity', 'relationship', 'life_event', 'reminder', 'file'])->get();

        $this->assertContains('reminder', $items->pluck('kind')->all());

        foreach ($items as $item) {
            $this->assertIsString($item->payload['created_at'] ?? null, "The {$item->kind} {$item->source_uuid} lost its creation date.");
        }
    }

    #[Test]
    public function it_never_writes_a_file_outside_the_folder_of_the_import(): void
    {
        $import = $this->read((string) json_encode([
            'version' => '2.0-preview.1',
            'account' => ['vaults' => [[
                'uuid' => '20000000-0000-4000-8000-000000000001',
                'name' => 'Scranton',
                'files' => [[
                    'uuid' => '../../../../escaped',
                    'created_at' => '2005-03-24T09:00:00Z',
                    'original_name' => 'escaped.txt',
                    'mime_type' => 'text/plain',
                    'data' => base64_encode('Creed was here'),
                ]],
            ]]],
        ], JSON_THROW_ON_ERROR));

        $item = $import->items()->where('kind', 'file')->sole();

        Storage::disk('local')->assertMissing('escaped');
        $this->assertSame([], collect(Storage::disk('local')->allFiles())->filter(fn (string $file): bool => Storage::disk('local')->get($file) === 'Creed was here')->all());
        $this->assertNull($item->source_uuid);
        $this->assertSame(AccountImportOutcomeEnum::LeftOut, $item->outcome);
    }

    #[Test]
    public function it_sets_aside_the_values_pointing_at_another_record_until_every_record_is_written(): void
    {
        $text = $this->exported([$this->dunderMifflin()]);
        $goat = collect($this->decoded($text)['account']['vaults'][0]['entities'])->firstWhere('name', "Mose's goat");

        $import = $this->read($text);

        $entity = $import->items()->where('kind', 'entity')->where('source_uuid', $goat['uuid'])->firstOrFail();
        $pointing = $import->items()->where('kind', 'related_values')->where('source_uuid', $goat['uuid'])->firstOrFail();

        $this->assertSame([], $entity->payload['values']);
        $this->assertEquals($goat['values'], $pointing->payload['values']);
        $this->assertGreaterThan($import->items()->whereIn('kind', ['contact', 'entity', 'life_event'])->max('position'), $pointing->position);
    }

    #[Test]
    public function it_ignores_the_keys_it_does_not_know(): void
    {
        $import = $this->importHolding(json_encode([
            'version' => '2.0-preview.1',
            'written_by' => 'Dwight Schrute',
            'account' => [
                'structures' => [],
                'life_event_types' => [],
                'relationship_types' => [],
                'beet_harvests' => ['Schrute Farms'],
                'vaults' => [[
                    'uuid' => '20000000-0000-4000-8000-000000000001',
                    'name' => 'Scranton',
                    'description' => null,
                    'colour' => 'blue',
                    'motto' => 'Bears, beets, Battlestar Galactica',
                    'contacts' => [[
                        'uuid' => '20000000-0000-4000-8000-000000000002',
                        'created_at' => '2026-01-01T00:00:00Z',
                        'is_me' => true,
                        'is_partial' => false,
                        'is_favourite' => false,
                        'is_archived' => false,
                        'favourite_beet' => 'Detroit Dark Red',
                        'name_representations' => [['is_display' => true, 'kind' => 'written', 'given_name' => 'Dwight']],
                        'values' => [['section' => 'notes', 'field' => 'note', 'row' => 1, 'parent_field' => 'notes', 'value' => 'Identity theft is not a joke.', 'ink' => 'red']],
                    ]],
                    'conference_rooms' => [['name' => 'The annex']],
                    'entities' => [],
                    'relationships' => [],
                    'life_events' => [],
                    'reminders' => [],
                    'files' => [],
                ]],
            ],
        ], JSON_THROW_ON_ERROR));

        new ReadMonicaFile(import: $import)->execute();

        $kept = (string) json_encode($import->items()->pluck('payload')->all());

        $this->assertSame(['contact', 'vault'], $import->items()->pluck('kind')->sort()->values()->all());
        $this->assertStringContainsString('Identity theft is not a joke.', $kept);

        foreach (['Dwight Schrute', 'Schrute Farms', 'Battlestar', 'Detroit Dark Red', 'ink', 'The annex'] as $unknown) {
            $this->assertStringNotContainsString($unknown, $kept);
        }
    }

    #[Test]
    public function it_refuses_a_version_it_does_not_know(): void
    {
        $import = $this->importHolding('{"version": "3.0-preview.1", "account": {"vaults": []}}');

        $this->expectException(ImportFileIsNotReadable::class);
        $this->expectExceptionMessage('(3.0-preview.1)');

        new ReadMonicaFile(import: $import)->execute();
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_an_export_of_this_application(): void
    {
        $import = $this->importHolding((string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        $this->expectException(ImportFileIsNotReadable::class);

        new ReadMonicaFile(import: $import)->execute();
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_complete(): void
    {
        $text = $this->exported([$this->dunderMifflin()]);
        $import = $this->importHolding(substr($text, 0, intdiv(strlen($text), 2)));

        $this->expectException(ImportFileIsNotReadable::class);

        new ReadMonicaFile(import: $import)->execute();
    }

    #[Test]
    public function it_reads_an_identifier_written_as_a_number(): void
    {
        $import = $this->read((string) json_encode([
            'version' => '2.0-preview.1',
            'account' => ['vaults' => [[
                'uuid' => '20000000-0000-4000-8000-000000000001',
                'name' => 'Scranton',
                'reminders' => [['uuid' => 4815, 'name' => 'Pretzel day']],
            ]]],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame('4815', $import->items()->where('kind', 'reminder')->sole()->source_uuid);
    }

    private function dunderMifflin(): Vault
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

    private function read(string $text): AccountImport
    {
        $import = $this->importHolding($text);

        new ReadMonicaFile(import: $import)->execute();

        return $import->refresh();
    }

    private function importHolding(string $text): AccountImport
    {
        $import = AccountImport::factory()->create();
        Storage::disk('local')->put($import->path, $text);

        return $import;
    }

    /** @return array<string, mixed> */
    private function decoded(string $text): array
    {
        return json_decode($text, true, flags: JSON_THROW_ON_ERROR);
    }
}
