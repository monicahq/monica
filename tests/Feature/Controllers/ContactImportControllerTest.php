<?php

namespace Tests\Feature\Controllers;

use App\Domains\Contact\ManageContact\Jobs\ProcessImportBatch;
use App\Models\ImportJob;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactImportControllerTest extends TestCase
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

    /** @test */
    public function it_shows_the_import_form(): void
    {
        $response = $this->actingAs($this->author)
            ->get(route('contact.import.create', ['vault' => $this->vault->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_denies_access_without_vault_editor_permission(): void
    {
        $viewer = $this->createUser();
        $vault = $this->createVaultUser($viewer, Vault::PERMISSION_VIEW);

        $response = $this->actingAs($viewer)
            ->get(route('contact.import.create', ['vault' => $vault->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_rejects_missing_file(): void
    {
        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /** @test */
    public function it_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('contacts.vcf', 52000);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_imports_a_vcf_file(): void
    {
        $fake = Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);

        $this->assertDatabaseHas('import_jobs', [
            'vault_id' => $this->vault->id,
            'file_type' => 'vcard',
            'total_rows' => 1,
        ]);

        $fake->assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->first() instanceof ProcessImportBatch;
        });
    }

    /** @test */
    public function it_imports_a_csv_file(): void
    {
        $fake = Bus::fake();

        $csv = "first_name,last_name,email\nJohn,Doe,john@example.com\nJane,Smith,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);

        $this->assertDatabaseHas('import_jobs', [
            'vault_id' => $this->vault->id,
            'file_type' => 'csv',
            'total_rows' => 2,
        ]);

        $fake->assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->first() instanceof ProcessImportBatch;
        });
    }

    /** @test */
    public function it_shows_import_progress(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'processing',
            'total_rows' => 10,
            'processed_rows' => 5,
        ]);

        $response = $this->actingAs($this->author)
            ->getJson(route('contact.import.progress', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $importJob->id,
            'status' => 'processing',
            'total_rows' => 10,
            'processed_rows' => 5,
        ]);
    }

    /** @test */
    public function it_shows_completed_import_status(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 10,
            'processed_rows' => 10,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->getJson(route('contact.import.progress', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'completed',
            'total_rows' => 10,
            'processed_rows' => 10,
        ]);
    }

    /** @test */
    public function it_shows_import_details(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 10,
            'processed_rows' => 8,
            'failed_rows' => 1,
            'skipped_rows' => 1,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.show', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_rejects_csv_with_no_header(): void
    {
        Bus::fake();

        $file = UploadedFile::fake()->createWithContent('bad.csv', "\n\n");

        $response = $this->actingAs($this->author)
            ->post(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function it_rejects_csv_with_no_data_rows(): void
    {
        Bus::fake();

        $file = UploadedFile::fake()->createWithContent('empty.csv', "first_name,last_name\n");

        $response = $this->actingAs($this->author)
            ->post(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function it_cancels_a_pending_import(): void
    {
        Bus::fake();

        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'pending',
            'total_rows' => 10,
        ]);

        $response = $this->actingAs($this->author)
            ->deleteJson(route('contact.import.cancel', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'cancelled']);

        $this->assertDatabaseHas('import_jobs', [
            'id' => $importJob->id,
            'status' => 'cancelled',
        ]);
        $this->assertNotNull($importJob->fresh()->completed_at);
    }

    /** @test */
    public function it_cancels_a_processing_import(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'processing',
            'total_rows' => 10,
        ]);

        $response = $this->actingAs($this->author)
            ->deleteJson(route('contact.import.cancel', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'cancelled']);

        $this->assertDatabaseHas('import_jobs', [
            'id' => $importJob->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_returns_409_when_import_already_completed(): void
    {
        Bus::fake();

        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 10,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->deleteJson(route('contact.import.cancel', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(409);
    }

    /** @test */
    public function it_returns_409_when_import_already_cancelled(): void
    {
        Bus::fake();

        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'cancelled',
            'total_rows' => 10,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->deleteJson(route('contact.import.cancel', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(409);
    }

    /** @test */
    public function it_denies_cancel_without_vault_editor_permission(): void
    {
        Bus::fake();

        $viewer = $this->createUser();
        $vault = $this->createVaultUser($viewer, Vault::PERMISSION_VIEW);

        $importJob = ImportJob::create([
            'account_id' => $viewer->account_id,
            'vault_id' => $vault->id,
            'user_id' => $viewer->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'processing',
            'total_rows' => 10,
        ]);

        $response = $this->actingAs($viewer)
            ->deleteJson(route('contact.import.cancel', [
                'vault' => $vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_rejects_duplicate_vcf_file_in_same_vault(): void
    {
        Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $hash = hash('sha256', $vcard);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.vcf',
            'original_filename' => 'prev.vcf',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 1,
            'completed_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /** @test */
    public function it_rejects_duplicate_csv_file_in_same_vault(): void
    {
        Bus::fake();

        $csv = "first_name,last_name,email\nJohn,Doe,john@example.com";
        $hash = hash('sha256', $csv);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.csv',
            'original_filename' => 'prev.csv',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'csv',
            'status' => 'completed',
            'total_rows' => 1,
            'completed_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /** @test */
    public function it_allows_same_file_in_different_vault(): void
    {
        Bus::fake();

        $otherVault = $this->createVaultUser($this->author, Vault::PERMISSION_EDIT);

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $hash = hash('sha256', $vcard);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.vcf',
            'original_filename' => 'prev.vcf',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 1,
            'completed_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $otherVault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_allows_different_content_with_same_filename(): void
    {
        Bus::fake();

        $originalVcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nEND:VCARD";
        $hash = hash('sha256', $originalVcard);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.vcf',
            'original_filename' => 'contacts.vcf',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 1,
            'completed_at' => now(),
        ]);

        $newVcard = "BEGIN:VCARD\nVERSION:3.0\nFN:Jane Smith\nEND:VCARD";
        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $newVcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_stores_content_hash_on_successful_import(): void
    {
        $fake = Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $hash = hash('sha256', $vcard);
        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $this->assertDatabaseHas('import_jobs', [
            'vault_id' => $this->vault->id,
            'content_hash' => $hash,
        ]);
    }

    // ─── Error CSV generation ────────────────────────────────────────────────

    /** @test */
    public function it_returns_404_for_errors_csv_when_no_errors(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'completed',
            'total_rows' => 1,
            'errors' => [],
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.errors.csv', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(404);
        $response->assertSee('No errors found');
    }

    /** @test */
    public function it_returns_404_for_errors_csv_when_file_missing(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/nonexistent.csv',
            'original_filename' => 'test.csv',
            'file_size' => 100,
            'file_type' => 'csv',
            'status' => 'failed',
            'total_rows' => 2,
            'errors' => [
                ['row' => 1, 'message' => 'Invalid email'],
            ],
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.errors.csv', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(404);
        $response->assertSee('Original file not found');
    }

    /** @test */
    public function it_generates_errors_csv_with_content(): void
    {
        Storage::fake();

        $csv = "name,email\nJohn,john@example.com\nJane,jane@example.com";
        Storage::put('imports/test.csv', $csv);

        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'file_type' => 'csv',
            'status' => 'failed',
            'total_rows' => 2,
            'errors' => [
                ['row' => 1, 'message' => 'Invalid email format'],
            ],
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.errors.csv', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="import_errors_'.$importJob->id.'.csv"');
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('"name","email","error"', $content);
        $this->assertStringContainsString('"John","john@example.com","Invalid email format"', $content);
        $this->assertStringNotContainsString('Jane', $content);
    }

    /** @test */
    public function it_handles_errors_csv_with_special_characters(): void
    {
        Storage::fake();

        $csv = "first_name,last_name,notes\nJohn,Doe,\"Needs \"\"urgent\"\" follow-up\"\nJane,Smith,\"Notes with, comma\"";
        Storage::put('imports/test.csv', $csv);

        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'file_type' => 'csv',
            'status' => 'failed',
            'total_rows' => 2,
            'errors' => [
                ['row' => 1, 'message' => 'Invalid phone'],
                ['row' => 2, 'message' => 'Bad date format'],
            ],
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.errors.csv', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('"John"', $content);
        $this->assertStringContainsString('"Jane"', $content);
        $this->assertStringContainsString('""urgent""', $content);
        $this->assertStringContainsString('"Notes with, comma"', $content);
        $this->assertStringContainsString('Invalid phone', $content);
        $this->assertStringContainsString('Bad date format', $content);
    }

    /** @test */
    public function it_handles_errors_csv_with_missing_rows(): void
    {
        Storage::fake();

        $csv = "name,email\nJohn,john@example.com";
        Storage::put('imports/test.csv', $csv);

        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_size' => strlen($csv),
            'file_type' => 'csv',
            'status' => 'failed',
            'total_rows' => 2,
            'errors' => [
                ['row' => 1, 'message' => 'Invalid email'],
                ['row' => 5, 'message' => 'Row not in file'],
            ],
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.errors.csv', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('John');
        $response->assertSee('Invalid email');
        $response->assertDontSee('Row not in file');
    }

    // ─── Status tracking edge cases ──────────────────────────────────────────

    /** @test */
    public function it_shows_progress_with_failed_status(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'failed',
            'total_rows' => 10,
            'processed_rows' => 0,
            'failed_rows' => 5,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->getJson(route('contact.import.progress', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'failed',
            'total_rows' => 10,
            'processed_rows' => 0,
            'failed_rows' => 5,
        ]);
    }

    /** @test */
    public function it_shows_progress_with_cancelled_status(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.vcf',
            'original_filename' => 'test.vcf',
            'file_size' => 100,
            'file_type' => 'vcard',
            'status' => 'cancelled',
            'total_rows' => 10,
            'processed_rows' => 3,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->getJson(route('contact.import.progress', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'cancelled',
            'total_rows' => 10,
            'processed_rows' => 3,
        ]);
    }

    /** @test */
    public function it_shows_progress_with_skipped_and_error_details(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_size' => 200,
            'file_type' => 'csv',
            'status' => 'completed',
            'total_rows' => 5,
            'processed_rows' => 3,
            'skipped_rows' => 1,
            'failed_rows' => 1,
            'errors' => [
                ['row' => 2, 'message' => 'Missing first name'],
                ['row' => 4, 'message' => 'Invalid email'],
            ],
            'error_log' => "[2025-01-01] Failed row 4\n",
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->getJson(route('contact.import.progress', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'completed',
            'total_rows' => 5,
            'processed_rows' => 3,
            'skipped_rows' => 1,
            'failed_rows' => 1,
        ]);
        $response->assertJsonCount(2, 'errors');
        $this->assertNotNull($response->json('errors_csv_url'));
    }

    // ─── Cache / invalidation edge cases ────────────────────────────────────

    /** @test */
    public function it_allows_reimport_after_failed_import(): void
    {
        Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $hash = hash('sha256', $vcard);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.vcf',
            'original_filename' => 'prev.vcf',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'vcard',
            'status' => 'failed',
            'total_rows' => 1,
            'completed_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_allows_reimport_after_cancelled_import(): void
    {
        Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $hash = hash('sha256', $vcard);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.vcf',
            'original_filename' => 'prev.vcf',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'vcard',
            'status' => 'cancelled',
            'total_rows' => 1,
            'completed_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_allows_reimport_after_pending_import(): void
    {
        Bus::fake();

        $vcard = "BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nN:Doe;John;;;\nEMAIL:john@example.com\nEND:VCARD";
        $hash = hash('sha256', $vcard);

        ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/prev.vcf',
            'original_filename' => 'prev.vcf',
            'file_size' => 100,
            'content_hash' => $hash,
            'file_type' => 'vcard',
            'status' => 'pending',
            'total_rows' => 1,
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

        $response = $this->actingAs($this->author)
            ->postJson(route('contact.import.store', ['vault' => $this->vault->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_shows_import_details_with_error_csv_url_when_errors_exist(): void
    {
        $importJob = ImportJob::create([
            'account_id' => $this->author->account_id,
            'vault_id' => $this->vault->id,
            'user_id' => $this->author->id,
            'file_path' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'file_size' => 100,
            'file_type' => 'csv',
            'status' => 'failed',
            'total_rows' => 2,
            'processed_rows' => 1,
            'failed_rows' => 1,
            'errors' => [
                ['row' => 2, 'message' => 'Invalid email'],
            ],
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->author)
            ->get(route('contact.import.show', [
                'vault' => $this->vault->id,
                'importJob' => $importJob->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($importJob->id);
        $response->assertSee('errors_csv_url');
    }
}
