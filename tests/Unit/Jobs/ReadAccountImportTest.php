<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Jobs\ReadAccountImport;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadAccountImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_monica_four_export_and_waits_for_the_person_to_confirm(): void
    {
        $import = $this->importOf((string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        ReadAccountImport::dispatchSync($import);

        $import->refresh();

        $this->assertSame(AccountImportStatusEnum::Read, $import->status);
        $this->assertSame(AccountImportSourceEnum::MonicaFour, $import->source_format);
        $this->assertSame(24, $import->items()->count());
        $this->assertNull($import->vault_id);
    }

    #[Test]
    public function it_deletes_the_uploaded_file_once_it_is_read_so_no_credential_stays_on_disk(): void
    {
        $import = $this->importOf((string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        ReadAccountImport::dispatchSync($import);

        Storage::disk('local')->assertMissing($import->path);

        foreach (Storage::disk('local')->allFiles() as $file) {
            $this->assertStringNotContainsString('google2fa_secret', (string) Storage::disk('local')->get($file));
        }
    }

    #[Test]
    public function it_deletes_everything_it_had_written_when_it_refuses_a_file(): void
    {
        $import = $this->importOf((string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-invalid.json')));

        ReadAccountImport::dispatchSync($import);

        Storage::disk('local')->assertMissing($import->directory());
        $this->assertNotNull($import->refresh()->ended_at);
    }

    #[Test]
    public function it_deletes_the_uploaded_file_when_reading_fails(): void
    {
        $import = $this->importOf('{"version": "1.0-preview.1"}');

        new ReadAccountImport(import: $import)->failed(null);

        $import->refresh();

        $this->assertSame(AccountImportStatusEnum::Refused, $import->status);
        $this->assertNotNull($import->ended_at);
        Storage::disk('local')->assertMissing($import->directory());
    }

    #[Test]
    public function it_reads_an_export_of_this_application_and_waits_for_the_person_to_confirm(): void
    {
        $import = $this->importOf('{"version": "2.0-preview.1", "account": {"structures": [], "life_event_types": [], "relationship_types": [], "vaults": [{"uuid": "20000000-0000-4000-8000-000000000001", "name": "Scranton", "description": null, "colour": "blue", "contacts": [], "entities": [], "relationships": [], "life_events": [], "reminders": [], "files": []}]}}');

        ReadAccountImport::dispatchSync($import);

        $import->refresh();

        $this->assertSame(AccountImportStatusEnum::Read, $import->status);
        $this->assertSame(AccountImportSourceEnum::Monica, $import->source_format);
        $this->assertSame(['vault'], $import->items()->pluck('kind')->all());
    }

    #[Test]
    public function it_refuses_a_file_it_cannot_read_and_says_why(): void
    {
        Log::spy();
        $import = $this->importOf((string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-invalid.json')));

        ReadAccountImport::dispatchSync($import);

        $import->refresh();

        $this->assertSame(AccountImportStatusEnum::Refused, $import->status);
        $this->assertStringContainsString('not complete', (string) $import->refusal);
        $this->assertSame(0, $import->items()->count());
        Log::shouldHaveReceived('warning')->with('An import file could not be read.', Mockery::on(fn (array $context): bool => $context['import'] === $import->id));
    }

    #[Test]
    public function it_refuses_a_file_that_is_no_export_at_all(): void
    {
        $import = $this->importOf('{"name": "Creed Bratton", "occupation": "quality assurance"}');

        ReadAccountImport::dispatchSync($import);

        $import->refresh();

        $this->assertSame(AccountImportStatusEnum::Refused, $import->status);
        $this->assertNull($import->source_format);
    }

    #[Test]
    public function it_reads_nothing_again_once_the_file_has_been_read(): void
    {
        Storage::fake('local');

        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Read]);
        $item = AccountImportItem::factory()->create(['account_id' => $import->account_id, 'account_import_id' => $import->id]);

        new ReadAccountImport(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::Read, $import->refresh()->status);
        $this->assertModelExists($item);
    }

    private function importOf(string $contents): AccountImport
    {
        Storage::fake('local');

        $import = AccountImport::factory()->create();
        Storage::disk('local')->put($import->path, $contents);

        return $import;
    }
}
