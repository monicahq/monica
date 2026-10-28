<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReadMonicaFourFile;
use App\Actions\StartAccountImport;
use App\Actions\WriteAccountExport;
use App\Enums\AccountImportStatusEnum;
use App\Enums\DefaultTemplateEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedRelationshipTypes;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\RelationshipType;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WriteAccountExportTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    private Vault $vault;

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

        $this->vault = $this->importedVault();
    }

    #[Test]
    public function it_opens_with_the_format_and_the_version_of_the_application(): void
    {
        $file = $this->decoded($this->exportedText());

        $this->assertSame('2.0-preview.1', $file['version']);
        $this->assertSame(config('monica.version'), $file['app_version']);
        $this->assertNotEmpty($file['exported_at']);
        $this->assertSame(['structures', 'life_event_types', 'relationship_types', 'vaults'], array_keys($file['account']));
        $this->assertSame([$this->vault->uuid], array_column($file['account']['vaults'], 'uuid'));
        $this->assertSame(
            ['uuid', 'name', 'description', 'colour', 'contacts', 'entities', 'relationships', 'life_events', 'reminders', 'files'],
            array_keys($file['account']['vaults'][0]),
        );
    }

    #[Test]
    public function it_carries_one_structure_document_for_every_version_a_record_is_on(): void
    {
        $file = $this->decoded($this->exportedText());
        $vault = $file['account']['vaults'][0];

        $versionsInUse = Contact::query()->where('vault_id', $this->vault->id)->pluck('structure_version_id')
            ->merge(Entity::query()->where('vault_id', $this->vault->id)->pluck('structure_version_id'))
            ->unique();

        $this->assertCount($versionsInUse->count(), $file['account']['structures']);
        $this->assertSame(['template'], array_values(array_unique(array_column(array_column(array_column($file['account']['structures'], 'document'), 'monica'), 'format'))));

        $described = array_map(fn (array $structure): array => ['template' => $structure['template'], 'version_number' => $structure['version_number']], $file['account']['structures']);

        foreach ([...$vault['contacts'], ...$vault['entities']] as $record) {
            $this->assertContains($record['structure'], $described);
        }
    }

    #[Test]
    public function it_writes_each_value_by_the_section_and_the_field_it_was_entered_in(): void
    {
        $file = $this->decoded($this->exportedText());
        $contacts = collect($file['account']['vaults'][0]['contacts']);

        $email = FieldValue::query()->where('field_key', 'email')->where('value_string', 'michael@dundermifflin.test')->firstOrFail();
        $me = $contacts->firstWhere('is_me', true);

        $this->assertContains([
            'section' => $email->field->sectionVersion->section->slug,
            'field' => 'email',
            'row' => $email->row?->position,
            'parent_field' => $email->field->parent?->key,
            'value' => 'michael@dundermifflin.test',
        ], array_map(fn (array $value): array => array_intersect_key($value, array_flip(['section', 'field', 'row', 'parent_field', 'value'])), $me['values']));

        $dwight = $contacts->firstWhere('uuid', $this->contactCalled('Dwight')->uuid);
        $birthday = collect($dwight['values'])->firstWhere('field', 'birthday');

        $this->assertTrue($birthday['year_is_unknown']);
        $this->assertSame('Dwight', collect($dwight['name_representations'])->firstWhere('is_display', true)['given_name']);
    }

    #[Test]
    public function it_names_the_types_the_account_made_itself_and_the_records_using_them(): void
    {
        $file = $this->decoded($this->exportedText());
        $vault = $file['account']['vaults'][0];

        $godfather = RelationshipType::query()->where('account_id', $this->michael->account_id)->whereNull('key')->firstOrFail();
        $firstKiss = LifeEventType::query()->where('account_id', $this->michael->account_id)->whereNull('key')->firstOrFail();

        $this->assertSame([$godfather->uuid], array_column($file['account']['relationship_types'], 'uuid'));
        $this->assertContains($godfather->uuid, array_column($vault['relationships'], 'type'));

        $ownLifeEventTypes = collect($file['account']['life_event_types'])->whereNull('key')->values();

        $this->assertSame([$firstKiss->uuid], $ownLifeEventTypes->pluck('uuid')->all());
        $this->assertSame('life_event_type', $ownLifeEventTypes[0]['document']['monica']['format']);
        $this->assertContains($firstKiss->uuid, array_column($vault['life_events'], 'type'));
        $this->assertContains(['life_event_type' => $firstKiss->uuid, 'version_number' => $ownLifeEventTypes[0]['version_number']], array_column($vault['life_events'], 'structure'));
        $this->assertCount(LifeEvent::query()->where('vault_id', $this->vault->id)->count(), $vault['life_events']);

        $uuids = [...array_column($vault['contacts'], 'uuid'), ...array_column($vault['entities'], 'uuid')];

        $this->assertNotEmpty($vault['reminders']);
        $this->assertNotEmpty($vault['entities']);

        foreach ($vault['reminders'] as $reminder) {
            $this->assertContains($reminder['record'], $uuids);
        }
    }

    #[Test]
    public function it_embeds_the_bytes_of_every_file_a_record_points_at(): void
    {
        $file = $this->decoded($this->exportedText());
        $vault = $file['account']['vaults'][0];

        $contact = Contact::query()->where('vault_id', $this->vault->id)->whereNotNull('avatar_file_id')->firstOrFail();
        $avatar = File::query()->findOrFail($contact->avatar_file_id);
        $entry = collect($vault['files'])->firstWhere('uuid', $avatar->uuid);

        $this->assertSame($avatar->uuid, collect($vault['contacts'])->firstWhere('uuid', $contact->uuid)['avatar_file']);
        $this->assertSame($avatar->checksum, $entry['checksum']);
        $this->assertSame(Storage::disk($avatar->disk)->get($avatar->path), base64_decode($entry['data'], true));
    }

    #[Test]
    public function it_leaves_out_deleted_records_detached_values_and_whatever_is_not_in_the_vaults_chosen(): void
    {
        $jim = $this->contactCalled('Jim');
        $dwight = $this->contactCalled('Dwight');
        $jim->delete();
        FieldValue::query()->where('valuable_id', $dwight->id)->where('field_key', 'birthday')->update(['is_detached' => true, 'detached_at' => now()]);
        $shareLink = ShareLink::factory()->create(['shareable_id' => $dwight->id]);
        $stamford = Contact::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $this->michael->account_id])->id]);

        $text = $this->exportedText();
        $dwightInTheFile = collect($this->decoded($text)['account']['vaults'][0]['contacts'])->firstWhere('uuid', $dwight->uuid);

        $this->assertStringNotContainsString($jim->uuid, $text);
        $this->assertNull(collect($dwightInTheFile['values'])->firstWhere('field', 'birthday'));
        $this->assertStringNotContainsString($shareLink->token, $text);
        $this->assertStringNotContainsString($stamford->uuid, $text);
    }

    private function exportedText(): string
    {
        $export = AccountExport::factory()->create([
            'user_id' => $this->michael->id,
            'vault_ids' => [$this->vault->id],
        ]);

        new WriteAccountExport(export: $export, path: $export->path)->execute();

        return (string) Storage::disk('local')->get($export->path);
    }

    /** @return array<string, mixed> */
    private function decoded(string $text): array
    {
        return json_decode($text, true, flags: JSON_THROW_ON_ERROR);
    }

    private function importedVault(): Vault
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

    private function contactCalled(string $firstName): Contact
    {
        return Contact::query()->where('vault_id', $this->vault->id)->where('first_name', $firstName)->firstOrFail();
    }
}
