<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = $this->createUser();
        $this->account = $this->user->account;
        $this->vault = $this->createVault($this->account);
        $this->setPermissionInVault($this->user, Vault::PERMISSION_MANAGE, $this->vault);
    }

    /** @test */
    public function test_import_initiation_validates_file_creates_record_and_dispatches_job()
    {
        Queue::fake();

        $csvContent = "first_name,last_name,email\nJohn,Doe,john@example.com\nJane,Smith,jane@example.com\n";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->postJson('/api/import', [
            'file' => $file,
            'vault_id' => $this->vault->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'total_rows',
                    'processed_rows',
                    'failed_rows',
                    'status',
                    'created_at',
                ],
            ]);

        $importId = $response->json('data.id');

        $this->assertDatabaseHas('import_jobs', [
            'id' => $importId,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'filename' => 'contacts.csv',
            'status' => ImportJob::STATUS_PENDING,
        ]);

        Queue::assertPushed(ProcessImportJob::class, function ($job) use ($importId) {
            return $job->importJobId === $importId;
        });

        // Contacts should NOT be created synchronously during HTTP request
        $this->assertEquals(0, Contact::where('vault_id', $this->vault->id)->count());
    }

    /** @test */
    public function test_import_initiation_fails_validation_if_missing_file_or_vault()
    {
        $response = $this->postJson('/api/import', []);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_background_processing_imports_valid_rows_and_updates_progress()
    {
        $csvContent = "first_name,last_name,email\nAlice,Wonderland,alice@example.com\nBob,Marley,bob@example.com\nCharlie,Brown,charlie@example.com\n";
        $path = 'imports/'.$this->account->id.'/valid_contacts.csv';
        Storage::disk('local')->put($path, $csvContent);

        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'filename' => 'valid_contacts.csv',
            'file_path' => $path,
            'status' => ImportJob::STATUS_PENDING,
        ]);

        $job = new ProcessImportJob($import->id);
        $job->handle();

        $import->refresh();

        $this->assertEquals(3, $import->total_rows);
        $this->assertEquals(3, $import->processed_rows);
        $this->assertEquals(0, $import->failed_rows);
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $import->status);
        $this->assertNotNull($import->completed_at);

        // Verify contacts created in vault
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $this->vault->id,
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
        ]);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $this->vault->id,
            'first_name' => 'Bob',
            'last_name' => 'Marley',
        ]);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $this->vault->id,
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
        ]);
    }

    /** @test */
    public function test_per_row_error_isolation_records_errors_and_continues_processing()
    {
        // Row 1: Valid
        // Row 2: Invalid (missing first_name)
        // Row 3: Invalid email format
        // Row 4: Valid
        $csvContent = "first_name,last_name,email\nDavid,Beckham,david@example.com\n,NoFirstName,test@example.com\nEva,Longoria,invalid-email-format\nFrank,Sinatra,frank@example.com\n";
        $path = 'imports/'.$this->account->id.'/mixed_contacts.csv';
        Storage::disk('local')->put($path, $csvContent);

        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'filename' => 'mixed_contacts.csv',
            'file_path' => $path,
            'status' => ImportJob::STATUS_PENDING,
        ]);

        $job = new ProcessImportJob($import->id);
        $job->handle();

        $import->refresh();

        $this->assertEquals(4, $import->total_rows);
        $this->assertEquals(4, $import->processed_rows);
        $this->assertEquals(2, $import->failed_rows);
        // Overall status should be completed because at least 1 contact imported successfully
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $import->status);

        // Verify error log records
        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $import->id,
            'row_number' => 2,
        ]);
        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $import->id,
            'row_number' => 3,
        ]);

        // Verify valid contacts created
        $this->assertDatabaseHas('contacts', ['vault_id' => $this->vault->id, 'first_name' => 'David']);
        $this->assertDatabaseHas('contacts', ['vault_id' => $this->vault->id, 'first_name' => 'Frank']);
    }

    /** @test */
    public function test_import_fails_if_all_rows_are_rejected()
    {
        $csvContent = "first_name,last_name,email\n,NoName1,email1@example.com\n,NoName2,email2@example.com\n";
        $path = 'imports/'.$this->account->id.'/all_invalid.csv';
        Storage::disk('local')->put($path, $csvContent);

        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'filename' => 'all_invalid.csv',
            'file_path' => $path,
            'status' => ImportJob::STATUS_PENDING,
        ]);

        $job = new ProcessImportJob($import->id);
        $job->handle();

        $import->refresh();

        $this->assertEquals(2, $import->total_rows);
        $this->assertEquals(2, $import->processed_rows);
        $this->assertEquals(2, $import->failed_rows);
        $this->assertEquals(ImportJob::STATUS_FAILED, $import->status);
        $this->assertNotNull($import->failure_message);
    }

    /** @test */
    public function test_progress_tracking_endpoint_returns_accurate_status_and_pct()
    {
        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'filename' => 'progress_test.csv',
            'total_rows' => 500,
            'processed_rows' => 320,
            'failed_rows' => 2,
            'status' => ImportJob::STATUS_PROCESSING,
            'started_at' => now()->subMinutes(2),
        ]);

        $response = $this->getJson("/api/import/{$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $import->id,
                    'filename' => 'progress_test.csv',
                    'total_rows' => 500,
                    'processed_rows' => 320,
                    'failed_rows' => 2,
                    'status' => 'processing',
                    'progress_pct' => 64,
                ],
            ]);
    }

    /** @test */
    public function test_progress_tracking_enforces_account_ownership()
    {
        $otherAccount = Account::factory()->create();
        $otherUser = User::factory()->create(['account_id' => $otherAccount->id]);

        $import = ImportJob::factory()->create([
            'account_id' => $otherAccount->id,
            'user_id' => $otherUser->id,
            'filename' => 'other_account.csv',
        ]);

        $response = $this->getJson("/api/import/{$import->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function test_retry_safety_resumes_from_checkpoint_without_creating_duplicates()
    {
        $csvContent = "first_name,last_name,email\nGeorge,Washington,george@example.com\nJohn,Adams,john.adams@example.com\nThomas,Jefferson,thomas@example.com\n";
        $path = 'imports/'.$this->account->id.'/retry_test.csv';
        Storage::disk('local')->put($path, $csvContent);

        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'filename' => 'retry_test.csv',
            'file_path' => $path,
            'has_header' => true,
            'header' => ['first_name', 'last_name', 'email'],
            'total_rows' => 3,
            'processed_rows' => 1, // Simulating Row 1 was already processed on initial run
            'failed_rows' => 0,
            'status' => ImportJob::STATUS_PENDING,
        ]);

        // Manually create Row 1 contact to mimic previous run success
        Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'first_name' => 'George',
            'last_name' => 'Washington',
        ]);

        // Execute job (simulating queue retry)
        $job = new ProcessImportJob($import->id);
        $job->handle();

        $import->refresh();

        $this->assertEquals(3, $import->total_rows);
        $this->assertEquals(3, $import->processed_rows);
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $import->status);

        // Ensure George Washington exists exactly ONCE (no duplicates created)
        $this->assertEquals(
            1,
            Contact::where('vault_id', $this->vault->id)
                ->where('first_name', 'George')
                ->where('last_name', 'Washington')
                ->count()
        );

        // Ensure remaining contacts were imported
        $this->assertDatabaseHas('contacts', ['vault_id' => $this->vault->id, 'first_name' => 'John']);
        $this->assertDatabaseHas('contacts', ['vault_id' => $this->vault->id, 'first_name' => 'Thomas']);
    }

    /** @test */
    public function test_import_cancellation_bonus()
    {
        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'status' => ImportJob::STATUS_PROCESSING,
        ]);

        $response = $this->postJson("/api/import/{$import->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => ImportJob::STATUS_CANCELLED,
                ],
            ]);

        $this->assertDatabaseHas('import_jobs', [
            'id' => $import->id,
            'status' => ImportJob::STATUS_CANCELLED,
        ]);
    }

    /** @test */
    public function test_downloadable_error_csv_bonus()
    {
        $import = ImportJob::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'status' => ImportJob::STATUS_COMPLETED,
        ]);

        ImportError::create([
            'import_job_id' => $import->id,
            'row_number' => 2,
            'row_data' => ['', 'Doe', 'invalid-email'],
            'error' => 'Missing required field: first_name',
        ]);

        $response = $this->getJson("/api/import/{$import->id}/errors");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Missing required field: first_name', $response->getContent());
    }
}
