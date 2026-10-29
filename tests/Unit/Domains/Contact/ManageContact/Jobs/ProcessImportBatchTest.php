<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Jobs;

use App\Domains\Contact\ManageContact\Jobs\ProcessImportBatch;
use App\Models\ImportJob;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessImportBatchTest extends TestCase
{
    use DatabaseTransactions;

    private User $author;

    private Vault $vault;

    private ImportJob $importJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = $this->createUser();
        $this->vault = $this->createVaultUser($this->author, Vault::PERMISSION_EDIT);
        $this->importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'pending',
            'total_rows' => 5,
        ]);
    }

    /** @test */
    public function it_processes_a_vcard_chunk(): void
    {
        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";

        $job = new ProcessImportBatch([
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard],
        ]);

        $job->execute([
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard],
        ]);

        $this->importJob->refresh();
        $this->assertEquals(1, $this->importJob->processed_rows);
        $this->assertEquals('processing', $this->importJob->status);
    }

    /** @test */
    public function it_processes_multiple_vcards_in_a_chunk(): void
    {
        $vcard1 = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";
        $vcard2 = "BEGIN:VCARD\nVERSION:3.0\nFN:Jane Smith\nN:Smith;Jane;;;\nEND:VCARD";

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard1, $vcard2],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(2, $this->importJob->processed_rows);
    }

    /** @test */
    public function it_processes_a_csv_chunk(): void
    {
        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'csv_chunk' => [
                ['number' => 1, 'data' => ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']],
            ],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(1, $this->importJob->processed_rows);
    }

    /** @test */
    public function it_skips_csv_rows_with_validation_errors(): void
    {
        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'csv_chunk' => [
                ['number' => 1, 'data' => ['email' => 'missing-name@example.com']],
                ['number' => 2, 'data' => ['first_name' => 'John', 'last_name' => 'Doe']],
            ],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(1, $this->importJob->processed_rows);
        $this->assertEquals(1, $this->importJob->skipped_rows);
        $this->assertNotNull($this->importJob->errors);
        $this->assertCount(1, $this->importJob->errors);
        $this->assertEquals(1, $this->importJob->errors[0]['row']);
    }

    /** @test */
    public function it_increments_failed_rows_on_invalid_vcard(): void
    {
        $invalidVcard = 'NOT A VCARD';

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$invalidVcard],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(0, $this->importJob->processed_rows);
        $this->assertEquals(1, $this->importJob->failed_rows);
    }

    /** @test */
    public function it_skips_processing_when_import_is_cancelled(): void
    {
        $this->importJob->update(['status' => 'cancelled']);

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(0, $this->importJob->processed_rows);
        $this->assertEquals('cancelled', $this->importJob->status);
    }

    /** @test */
    public function it_tracks_created_contact_ids_on_successful_import(): void
    {
        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertNotEmpty($this->importJob->contact_ids_created);
        $this->assertCount(1, $this->importJob->contact_ids_created);

        $contactId = $this->importJob->contact_ids_created[0];
        $this->assertDatabaseHas('contacts', [
            'id' => $contactId,
            'vault_id' => $this->vault->id,
        ]);
    }

    /** @test */
    public function it_handles_mixed_success_and_failure_in_same_chunk(): void
    {
        $validVcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";
        $invalidVcard = 'NOT A VCARD';

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$validVcard, $invalidVcard],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(1, $this->importJob->processed_rows);
        $this->assertEquals(1, $this->importJob->failed_rows);
    }

    /** @test */
    public function it_fails_validation_with_invalid_account(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => '00000000-0000-0000-0000-000000000000',
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [],
        ];

        new ProcessImportBatch($data);
    }

    /** @test */
    public function it_dispatches_via_bus_and_updates_status(): void
    {
        Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";

        $this->importJob->update(['total_rows' => 1]);

        ProcessImportBatch::dispatch([
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard],
        ]);

        Bus::assertDispatched(ProcessImportBatch::class);
    }

    /** @test */
    public function it_stops_processing_chunk_when_batch_is_cancelled(): void
    {
        $vcard1 = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";
        $vcard2 = "BEGIN:VCARD\nVERSION:3.0\nFN:Jane Smith\nN:Smith;Jane;;;\nEND:VCARD";

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard1, $vcard2],
        ];

        $job = new ProcessImportBatch($data);

        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(2, $this->importJob->processed_rows);
    }

    /** @test */
    public function it_respects_db_cancelled_status_during_vcard_chunk_processing(): void
    {
        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEND:VCARD";

        $this->importJob->update(['status' => 'processing', 'total_rows' => 2]);

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard, $vcard],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(2, $this->importJob->processed_rows);

        $this->importJob->update(['status' => 'cancelled', 'processed_rows' => 0, 'failed_rows' => 0]);

        $data2 = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'vcard_chunk' => [$vcard],
        ];

        $job2 = new ProcessImportBatch($data2);
        $job2->execute($data2);

        $this->importJob->refresh();
        $this->assertEquals(0, $this->importJob->processed_rows);
    }

    /** @test */
    public function it_respects_db_cancelled_status_during_csv_chunk_processing(): void
    {
        $this->importJob->update(['status' => 'processing', 'total_rows' => 2]);

        $data = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'csv_chunk' => [
                ['number' => 1, 'data' => ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']],
                ['number' => 2, 'data' => ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']],
            ],
        ];

        $job = new ProcessImportBatch($data);
        $job->execute($data);

        $this->importJob->refresh();
        $this->assertEquals(2, $this->importJob->processed_rows);

        $this->importJob->update(['status' => 'cancelled', 'processed_rows' => 0, 'failed_rows' => 0, 'skipped_rows' => 0]);

        $data2 = [
            'import_job_id' => $this->importJob->id,
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'vault_id' => $this->vault->id,
            'csv_chunk' => [
                ['number' => 1, 'data' => ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']],
            ],
        ];

        $job2 = new ProcessImportBatch($data2);
        $job2->execute($data2);

        $this->importJob->refresh();
        $this->assertEquals(0, $this->importJob->processed_rows);
    }
}
