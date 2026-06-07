<?php

namespace Tests\Feature\Api;

use App\Models\ImportJob;
use App\Models\ImportError;
use App\Enums\ImportJobStatus;
use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\Vault;
use App\Jobs\ProcessImportJob;
use App\Jobs\ProcessImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Helper to setup a valid user, vault, and default contact information types.
     */
    protected function setupUserAndVault(): array
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $emailType = ContactInformationType::firstOrCreate([
            'account_id' => $user->account_id,
            'type' => 'email',
        ], [
            'name_translation_key' => 'Email address',
            'protocol' => 'mailto:',
        ]);

        $phoneType = ContactInformationType::firstOrCreate([
            'account_id' => $user->account_id,
            'type' => 'phone',
        ], [
            'name_translation_key' => 'Phone',
            'protocol' => 'tel:',
        ]);

        return [$user, $vault, $emailType, $phoneType];
    }

    public function test_submitting_import_requires_authenticated_user()
    {
        $response = $this->postJson('/api/import', [
            'vault_id' => '00000000-0000-0000-0000-000000000000',
            'file' => UploadedFile::fake()->create('contacts.csv', 100),
        ]);

        $response->assertStatus(401);
    }

    public function test_submitting_import_requires_valid_vault()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/import', [
            'vault_id' => '00000000-0000-0000-0000-000000000000',
            'file' => UploadedFile::fake()->create('contacts.csv', 100),
        ]);

        $response->assertStatus(404);
    }

    public function test_submitting_import_validates_file()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $response = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            // no file parameter
        ]);
        $response->assertStatus(422);

        $response2 = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);
        $response2->assertStatus(422);
    }

    public function test_submitting_valid_import_succeeds_and_dispatches_job()
    {
        Queue::fake();
        [$user, $vault] = $this->setupUserAndVault();

        $csvContent = "first_name,last_name,email\nJane,Doe,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'filename',
                'status',
                'file_path',
                'file_hash',
            ]
        ]);

        $this->assertDatabaseHas('import_jobs', [
            'vault_id' => $vault->id,
            'filename' => 'contacts.csv',
            'status' => 'pending',
        ]);

        $jobId = $response->json('data.id');
        Queue::assertPushed(ProcessImportJob::class, function ($job) use ($jobId) {
            return $this->getPrivateValue($job, 'importJobId') === $jobId;
        });
    }

    public function test_duplicate_upload_is_prevented()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $csvContent = "first_name,last_name,email\nJane,Doe,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        // First upload
        $response1 = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);
        $response1->assertStatus(201);

        // Second upload with identical content
        $response2 = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);
        $response2->assertStatus(409);
        $response2->assertJsonFragment([
            'error_code' => 409
        ]);
    }

    public function test_process_import_job_counts_rows_and_dispatches_batches()
    {
        Queue::fake();
        [$user, $vault] = $this->setupUserAndVault();

        // Write a mock CSV to fake storage with 52 rows
        $csvLines = ["first_name,last_name,email"];
        for ($i = 1; $i <= 52; $i++) {
            $csvLines[] = "FirstName{$i},LastName{$i},email{$i}@example.com";
        }
        $csvContent = implode("\n", $csvLines);
        $filePath = 'imports/test_job.csv';
        Storage::disk('local')->put($filePath, $csvContent);

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'test_job.csv',
            'file_path' => $filePath,
            'file_hash' => hash('sha256', $csvContent),
            'total_rows' => 0,
            'status' => 'pending',
        ]);

        (new ProcessImportJob($importJob->id))->handle();

        $importJob->refresh();
        $this->assertEquals(52, $importJob->total_rows);
        $this->assertEquals(ImportJobStatus::PROCESSING, $importJob->status);
        $this->assertNotNull($importJob->started_at);

        // Should dispatch 2 batches: one with 50 rows, one with 2 rows.
        Queue::assertPushed(ProcessImportBatch::class);

        // File should be deleted after processing
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_process_import_batch_creates_contacts_and_isolates_errors()
    {
        [$user, $vault, $emailType, $phoneType] = $this->setupUserAndVault();

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'test_batch.csv',
            'file_path' => 'imports/test_batch.csv',
            'file_hash' => 'dummyhash',
            'total_rows' => 4,
            'status' => 'processing',
        ]);

        $rows = [
            [
                'row_number' => 1,
                'mapped' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '12345678',
                ],
                'raw' => [
                    'First Name' => 'John',
                    'Last Name' => 'Doe',
                    'Email Address' => 'john@example.com',
                    'Phone Number' => '12345678',
                ]
            ],
            [
                'row_number' => 2,
                'mapped' => [
                    'first_name' => '',
                    'last_name' => '',
                    'nickname' => '',
                ],
                'raw' => [
                    'First Name' => '',
                    'Last Name' => '',
                    'Email Address' => '',
                ]
            ],
            [
                'row_number' => 3,
                'mapped' => [
                    'first_name' => 'InvalidEmailGuy',
                    'email' => 'bad-email-format',
                ],
                'raw' => [
                    'First Name' => 'InvalidEmailGuy',
                    'Email Address' => 'bad-email-format',
                ]
            ],
            [
                'row_number' => 4,
                'mapped' => [
                    'nickname' => 'NickOnly',
                ],
                'raw' => [
                    'Nickname' => 'NickOnly',
                ]
            ],
        ];

        (new ProcessImportBatch($importJob->id, $rows))->handle();

        $importJob->refresh();

        // 2 succeeded, 2 failed
        $this->assertEquals(2, $importJob->processed_rows);
        $this->assertEquals(2, $importJob->failed_rows);
        $this->assertEquals(ImportJobStatus::COMPLETED, $importJob->status);
        $this->assertNotNull($importJob->completed_at);

        // Verify John Doe created with details
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $contact = Contact::where('first_name', 'John')->first();
        $this->assertNotNull($contact);

        $this->assertDatabaseHas('contact_information', [
            'contact_id' => $contact->id,
            'type_id' => $emailType->id,
            'data' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('contact_information', [
            'contact_id' => $contact->id,
            'type_id' => $phoneType->id,
            'data' => '12345678',
        ]);

        // Verify NickOnly created
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'nickname' => 'NickOnly',
        ]);

        // Verify row-level failures were written to import_errors
        $this->assertCount(2, $importJob->errors()->get());
        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $importJob->id,
            'row_number' => 2,
            'error_message' => 'At least one name field (first name, last name, nickname, or full name) is required.',
        ]);
        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $importJob->id,
            'row_number' => 3,
            'error_message' => 'Invalid email address format.',
        ]);
    }

    public function test_get_import_progress()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'progress.csv',
            'file_path' => 'imports/progress.csv',
            'file_hash' => 'hash1',
            'total_rows' => 100,
            'processed_rows' => 40,
            'failed_rows' => 10,
            'status' => 'processing',
            'started_at' => now()->subSeconds(20),
        ]);

        $response = $this->getJson("/api/import/{$importJob->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'processing',
            'total_rows' => 100,
            'processed_rows' => 40,
            'failed_rows' => 10,
            'progress_pct' => 50.0, // (40+10)/105 = 50%
        ]);

        // Rate is 50 processed rows per 20 seconds = 2.5 rows/sec.
        // Remaining is 50 rows. 50 / 2.5 = 20 seconds.
        $this->assertEquals(20, $response->json('data.estimated_remaining_sec'));
    }

    public function test_cancel_import_job()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'cancel.csv',
            'file_path' => 'imports/cancel.csv',
            'file_hash' => 'hash2',
            'total_rows' => 100,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $response = $this->postJson("/api/import/{$importJob->id}/cancel");
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'cancelling',
        ]);

        $importJob->refresh();
        $this->assertEquals(ImportJobStatus::CANCELLING, $importJob->status);
        $this->assertNotNull($importJob->cancelled_at);
        $this->assertNull($importJob->completed_at);

        // Ensure subsequent ProcessImportBatch processing is skipped
        $rows = [[
            'row_number' => 1,
            'mapped' => ['first_name' => 'CancelCheck'],
            'raw' => ['First Name' => 'CancelCheck']
        ]];
        (new ProcessImportBatch($importJob->id, $rows))->handle();

        $importJob->refresh();
        $this->assertEquals(ImportJobStatus::CANCELLED, $importJob->status);
        $this->assertNotNull($importJob->completed_at);

        $this->assertDatabaseMissing('contacts', [
            'first_name' => 'CancelCheck'
        ]);
    }

    public function test_download_error_csv()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'errors.csv',
            'file_path' => 'imports/errors.csv',
            'file_hash' => 'hash3',
            'total_rows' => 100,
            'status' => 'failed',
        ]);

        ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 2,
            'row_data' => [
                'First Name' => '',
                'Last Name' => '',
                'Email Address' => 'xyz',
            ],
            'error_message' => 'Validation error message example',
        ]);

        $response = $this->get("/api/import/{$importJob->id}/errors.csv");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $expectedCsv = "\"First Name\",\"Last Name\",\"Email Address\",error_message\r\n,,xyz,\"Validation error message example\"\r\n";
        
        // Normalize line endings for comparison
        $this->assertEquals(
            str_replace("\r\n", "\n", $expectedCsv),
            str_replace("\r\n", "\n", $content)
        );
    }

    public function test_stuck_import_recovery_command()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $stuckJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'stuck.csv',
            'file_path' => 'imports/stuck.csv',
            'file_hash' => 'hash4',
            'total_rows' => 100,
            'status' => 'processing',
            'started_at' => now()->subMinutes(5),
            'last_heartbeat_at' => now()->subMinutes(35),
        ]);

        $activeJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'active.csv',
            'file_path' => 'imports/active.csv',
            'file_hash' => 'hash5',
            'total_rows' => 100,
            'status' => 'processing',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->artisan('monica:recover-imports')
            ->expectsOutput("Recovered stuck import job ID: {$stuckJob->id}")
            ->expectsOutput("Recovery complete. Found and resolved 1 stuck import job(s).")
            ->assertExitCode(0);

        $stuckJob->refresh();
        $activeJob->refresh();

        $this->assertEquals(ImportJobStatus::FAILED, $stuckJob->status);
        $this->assertNotNull($stuckJob->completed_at);
        $this->assertCount(1, $stuckJob->errors()->get());
        $this->assertStringContainsString('Import job timed out', $stuckJob->errors()->first()->error_message);

        $this->assertEquals(ImportJobStatus::PROCESSING, $activeJob->status);
        $this->assertNull($activeJob->completed_at);
    }

    public function test_index_filters_imports_by_vault_id()
    {
        [$user, $vault] = $this->setupUserAndVault();
        $otherVault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $importJob1 = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'vault1.csv',
            'file_path' => 'imports/vault1.csv',
            'file_hash' => 'hash1',
            'total_rows' => 10,
        ]);

        $importJob2 = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $otherVault->id,
            'filename' => 'vault2.csv',
            'file_path' => 'imports/vault2.csv',
            'file_hash' => 'hash2',
            'total_rows' => 20,
        ]);

        $response = $this->getJson("/api/import?vault_id={$vault->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($importJob1->id, $response->json('data.0.id'));
    }

    public function test_process_import_job_handles_utf8_bom()
    {
        Queue::fake();
        [$user, $vault] = $this->setupUserAndVault();

        // Write a mock CSV with UTF-8 BOM
        $bom = "\xEF\xBB\xBF";
        $csvContent = $bom . "first_name,last_name,email\nJane,Doe,jane@example.com";
        $filePath = 'imports/bom_test.csv';
        Storage::disk('local')->put($filePath, $csvContent);

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'bom_test.csv',
            'file_path' => $filePath,
            'file_hash' => hash('sha256', $csvContent),
            'total_rows' => 0,
            'status' => 'pending',
        ]);

        (new ProcessImportJob($importJob->id))->handle();

        // If BOM was stripped, it should successfully parse the rows and queue the batch.
        Queue::assertPushed(ProcessImportBatch::class, function ($job) {
            $rows = $this->getPrivateValue($job, 'rows');
            return $rows[0]['mapped']['first_name'] === 'Jane';
        });
    }

    public function test_submitting_import_deletes_file_on_exception()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $csvContent = "first_name,last_name,email\nJane,Doe,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        // Upload first time
        $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);

        // Upload second time (triggers DuplicateImportJobException)
        $response = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);
        $response->assertStatus(409);

        // Check that all files are cleaned up (first one processed/deleted, second aborted/deleted)
        $files = Storage::disk('local')->allFiles('imports');
        $this->assertCount(0, $files);
    }

    public function test_errors_endpoint_returns_paginated_errors()
    {
        [$user, $vault] = $this->setupUserAndVault();

        $importJob = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id'    => $user->id,
            'vault_id'   => $vault->id,
            'filename'   => 'pagerrors.csv',
            'file_path'  => 'imports/pagerrors.csv',
            'file_hash'  => 'hashpaged',
            'total_rows' => 3,
            'status'     => 'completed',
        ]);

        ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 1,
            'row_data' => [],
            'error_message' => 'Error on row 1',
        ]);
        ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 2,
            'row_data' => [],
            'error_message' => 'Error on row 2',
        ]);
        ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 3,
            'row_data' => [],
            'error_message' => 'Error on row 3',
        ]);

        $response = $this->getJson("/api/import/{$importJob->id}/errors?per_page=2&page=1");
        $response->assertStatus(200);

        // Verify data contains 2 items (page 1, per_page 2)
        $response->assertJsonStructure([
            'data' => [
                '*' => ['row', 'message'],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(3, $response->json('meta.total'));
        $this->assertEquals('Error on row 1', $response->json('data.0.message'));
        $this->assertEquals(1, $response->json('data.0.row'));
        $this->assertEquals(1, $response->json('data.0.row_number'));
    }

    public function test_index_returns_pagination_metadata()
    {
        [$user, $vault] = $this->setupUserAndVault();

        ImportJob::create([
            'account_id' => $user->account_id,
            'user_id'    => $user->id,
            'vault_id'   => $vault->id,
            'filename'   => 'meta_test.csv',
            'file_path'  => 'imports/meta_test.csv',
            'file_hash'  => 'hashmeta1',
            'total_rows' => 10,
            'processed_rows' => 10,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/import?page=1&per_page=10');
        $response->assertStatus(200);

        // Assignment requires: current_page, per_page, total, last_page
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'filename',
                    'total_rows',
                    'processed_rows',
                    'failed_rows',
                    'status',
                    'progress_pct',
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    public function test_submitting_import_requires_vault_editor_permission()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, \App\Models\Vault::PERMISSION_VIEW);

        $csvContent = "first_name,last_name,email\nJane,Doe,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->actingAs($user)->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_listing_imports_only_returns_permitted_vaults()
    {
        $user = $this->createUser();
        $myVault = $this->createVaultUser($user, \App\Models\Vault::PERMISSION_VIEW);
        
        // Create another vault that belongs to the account, but user has NO access to
        $otherVault = \App\Models\Vault::create([
            'account_id' => $user->account_id,
            'name' => 'Secret Vault',
            'type' => \App\Models\Vault::TYPE_PERSONAL,
        ]);

        $job1 = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $myVault->id,
            'filename' => 'mine.csv',
            'file_path' => 'imports/mine.csv',
            'file_hash' => 'hash1',
            'total_rows' => 10,
        ]);

        $job2 = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $otherVault->id,
            'filename' => 'other.csv',
            'file_path' => 'imports/other.csv',
            'file_hash' => 'hash2',
            'total_rows' => 20,
        ]);

        // 1. Without filtering, we should only see mine.csv, not other.csv
        $response = $this->actingAs($user)->getJson('/api/import');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($job1->id, $response->json('data.0.id'));

        // 2. Explicitly querying other vault should return 403
        $response2 = $this->actingAs($user)->getJson("/api/import?vault_id={$otherVault->id}");
        $response2->assertStatus(403);
    }

    public function test_showing_import_job_requires_vault_view_permission()
    {
        $user = $this->createUser();
        $otherVault = \App\Models\Vault::create([
            'account_id' => $user->account_id,
            'name' => 'Secret Vault',
            'type' => \App\Models\Vault::TYPE_PERSONAL,
        ]);

        $job = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $otherVault->id,
            'filename' => 'other.csv',
            'file_path' => 'imports/other.csv',
            'file_hash' => 'hash2',
            'total_rows' => 20,
        ]);

        $response = $this->actingAs($user)->getJson("/api/import/{$job->id}");
        $response->assertStatus(403);
    }

    public function test_cancelling_import_job_requires_vault_edit_permission()
    {
        $user = $this->createUser();
        // User has only VIEW permission, not EDIT
        $vault = $this->createVaultUser($user, \App\Models\Vault::PERMISSION_VIEW);

        $job = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => 'cancel.csv',
            'file_path' => 'imports/cancel.csv',
            'file_hash' => 'hash2',
            'total_rows' => 100,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson("/api/import/{$job->id}/cancel");
        $response->assertStatus(403);
    }

    public function test_viewing_import_errors_requires_vault_view_permission()
    {
        $user = $this->createUser();
        $otherVault = \App\Models\Vault::create([
            'account_id' => $user->account_id,
            'name' => 'Secret Vault',
            'type' => \App\Models\Vault::TYPE_PERSONAL,
        ]);

        $job = ImportJob::create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $otherVault->id,
            'filename' => 'errors.csv',
            'file_path' => 'imports/errors.csv',
            'file_hash' => 'hash3',
            'total_rows' => 100,
            'status' => 'failed',
        ]);

        ImportError::create([
            'import_job_id' => $job->id,
            'row_number' => 2,
            'row_data' => [],
            'error_message' => 'Error',
        ]);

        // 1. Errors JSON endpoint
        $response = $this->actingAs($user)->getJson("/api/import/{$job->id}/errors");
        $response->assertStatus(403);

        // 2. Errors CSV download endpoint
        $response2 = $this->actingAs($user)->get("/api/import/{$job->id}/errors.csv");
        $response2->assertStatus(403);
    }
}
