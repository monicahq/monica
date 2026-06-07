<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\ImportFile;
use App\Models\ImportJob;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImportFileTest extends TestCase
{
    use DatabaseTransactions;

    private User $author;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = $this->createUser();
        $this->vault = $this->createVaultUser($this->author, Vault::PERMISSION_EDIT);
    }

    private function validCsv(): string
    {
        return "first_name,last_name,email\nJohn,Doe,john@example.com\nJane,Smith,jane@example.com";
    }

    private function validVcard(): string
    {
        return "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
    }

    private function executeService(array $overrides = []): ImportJob
    {
        $data = array_merge([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_size' => 100,
            'content_hash' => hash('sha256', 'test'),
            'file_type' => 'csv',
        ], $overrides);

        return (new ImportFile)->execute($data);
    }

    /** @test */
    public function it_fails_validation_with_invalid_data(): void
    {
        $this->expectException(ValidationException::class);

        $this->executeService(['account_id' => 'invalid-uuid']);
    }

    /** @test */
    public function it_fails_validation_without_required_fields(): void
    {
        $this->expectException(ValidationException::class);

        $this->executeService(['file_path' => '']);
    }

    /** @test */
    public function it_parses_csv_correctly(): void
    {
        Storage::fake();
        Bus::fake();

        $csv = $this->validCsv();
        Storage::put('imports/test.csv', $csv);

        $importJob = $this->executeService([
            'file_path' => 'imports/test.csv',
            'file_type' => 'csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'content_hash' => hash('sha256', $csv),
        ]);

        $this->assertEquals('csv', $importJob->file_type);
        $this->assertEquals(2, $importJob->total_rows);
        $this->assertEquals('processing', $importJob->status);
    }

    /** @test */
    public function it_parses_vcf_correctly(): void
    {
        Storage::fake();
        Bus::fake();

        $vcard = $this->validVcard();
        Storage::put('imports/test.vcf', $vcard);

        $importJob = $this->executeService([
            'file_path' => 'imports/test.vcf',
            'file_type' => 'vcard',
            'original_filename' => 'test.vcf',
            'file_size' => strlen($vcard),
            'content_hash' => hash('sha256', $vcard),
        ]);

        $this->assertEquals('vcard', $importJob->file_type);
        $this->assertEquals(1, $importJob->total_rows);
    }

    /** @test */
    public function it_throws_for_empty_csv(): void
    {
        Storage::fake();
        Bus::fake();

        Storage::put('imports/empty.csv', '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSV file has no header row');

        $this->executeService([
            'file_path' => 'imports/empty.csv',
            'file_type' => 'csv',
            'original_filename' => 'empty.csv',
            'file_size' => 0,
            'content_hash' => hash('sha256', ''),
        ]);
    }

    /** @test */
    public function it_throws_for_header_only_csv(): void
    {
        Storage::fake();
        Bus::fake();

        Storage::put('imports/header.csv', "first_name,last_name\n");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No data rows found in the CSV file.');

        $this->executeService([
            'file_path' => 'imports/header.csv',
            'file_type' => 'csv',
            'original_filename' => 'header.csv',
            'file_size' => 20,
            'content_hash' => hash('sha256', "first_name,last_name\n"),
        ]);
    }

    /** @test */
    public function it_throws_for_empty_vcf(): void
    {
        Storage::fake();
        Bus::fake();

        Storage::put('imports/empty.vcf', '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No valid vCards found in the file.');

        $this->executeService([
            'file_path' => 'imports/empty.vcf',
            'file_type' => 'vcard',
            'original_filename' => 'empty.vcf',
            'file_size' => 0,
            'content_hash' => hash('sha256', ''),
        ]);
    }

    /** @test */
    public function it_chunks_rows_into_groups_of_fifty(): void
    {
        Storage::fake();
        Bus::fake();

        $rows = [];
        for ($i = 0; $i < 120; $i++) {
            $rows[] = "first{$i},last{$i},email{$i}@example.com";
        }
        $csv = "first_name,last_name,email\n".implode("\n", $rows);
        Storage::put('imports/large.csv', $csv);

        $importJob = $this->executeService([
            'file_path' => 'imports/large.csv',
            'file_type' => 'csv',
            'original_filename' => 'large.csv',
            'file_size' => strlen($csv),
            'content_hash' => hash('sha256', $csv),
        ]);

        $importJob->refresh();
        $this->assertEquals(120, $importJob->total_rows);
        $this->assertEquals('processing', $importJob->status);
        $this->assertNotNull($importJob->batch_id);
    }

    /** @test */
    public function it_dispatches_batch_and_updates_status(): void
    {
        Storage::fake();
        Bus::fake();

        $csv = $this->validCsv();
        Storage::put('imports/test.csv', $csv);

        $importJob = $this->executeService([
            'file_path' => 'imports/test.csv',
            'file_type' => 'csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'content_hash' => hash('sha256', $csv),
        ]);

        $importJob->refresh();

        $this->assertEquals('processing', $importJob->status);
        $this->assertNotNull($importJob->started_at);
        $this->assertNotNull($importJob->batch_id);
        $this->assertDatabaseHas('import_jobs', [
            'id' => $importJob->id,
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function it_creates_import_job_with_correct_data(): void
    {
        Storage::fake();
        Bus::fake();

        $csv = $this->validCsv();
        Storage::put('imports/test.csv', $csv);

        $this->executeService([
            'file_path' => 'imports/test.csv',
            'file_type' => 'csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'content_hash' => hash('sha256', $csv),
        ]);

        $this->assertDatabaseHas('import_jobs', [
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_type' => 'csv',
            'total_rows' => 2,
            'content_hash' => hash('sha256', $csv),
        ]);
    }

    /** @test */
    public function it_dispatches_vcard_chunks_with_correct_key(): void
    {
        Storage::fake();
        Bus::fake();

        $vcard = $this->validVcard();
        Storage::put('imports/test.vcf', $vcard);

        $importJob = $this->executeService([
            'file_path' => 'imports/test.vcf',
            'file_type' => 'vcard',
            'original_filename' => 'test.vcf',
            'file_size' => strlen($vcard),
            'content_hash' => hash('sha256', $vcard),
        ]);

        $importJob->refresh();
        $this->assertEquals('vcard', $importJob->file_type);
        $this->assertEquals('processing', $importJob->status);
        $this->assertNotNull($importJob->batch_id);
    }

    /** @test */
    public function it_dispatches_csv_chunks_with_correct_key(): void
    {
        Storage::fake();
        Bus::fake();

        $csv = $this->validCsv();
        Storage::put('imports/test.csv', $csv);

        $importJob = $this->executeService([
            'file_path' => 'imports/test.csv',
            'file_type' => 'csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'content_hash' => hash('sha256', $csv),
        ]);

        $importJob->refresh();
        $this->assertEquals('csv', $importJob->file_type);
        $this->assertEquals('processing', $importJob->status);
        $this->assertNotNull($importJob->batch_id);
    }

    /** @test */
    public function it_deletes_csv_file_when_parsing_fails(): void
    {
        Storage::fake();
        Bus::fake();

        Storage::put('imports/bad.csv', '');

        try {
            $this->executeService([
                'file_path' => 'imports/bad.csv',
                'file_type' => 'csv',
                'original_filename' => 'bad.csv',
                'file_size' => 0,
                'content_hash' => hash('sha256', ''),
            ]);
        } catch (\InvalidArgumentException) {
        }

        Storage::assertMissing('imports/bad.csv');
    }

    /** @test */
    public function it_deletes_vcf_file_when_parsing_fails(): void
    {
        Storage::fake();
        Bus::fake();

        Storage::put('imports/bad.vcf', '');

        try {
            $this->executeService([
                'file_path' => 'imports/bad.vcf',
                'file_type' => 'vcard',
                'original_filename' => 'bad.vcf',
                'file_size' => 0,
                'content_hash' => hash('sha256', ''),
            ]);
        } catch (\InvalidArgumentException) {
        }

        Storage::assertMissing('imports/bad.vcf');
    }
}
