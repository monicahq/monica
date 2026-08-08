<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessImportJob;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_validates_the_uploaded_file()
    {
        $user = $this->createUser();
        
        $response = $this->postJson('/api/import', [
            'vault_id' => 'not-a-uuid',
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('error.error_code', 32);
            
        $response = $this->postJson('/api/import', [
            'vault_id' => \Illuminate\Support\Str::uuid(),
            'file' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.error_code', 32);
    }

    public function test_it_creates_an_import_record_and_dispatches_job()
    {
        Queue::fake();
        Storage::fake('local');

        $user = $this->createUser();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        $file = UploadedFile::fake()->create('contacts.csv', 100, 'text/csv');

        $response = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.filename', 'contacts.csv')
            ->assertJsonPath('data.status', 'pending');

        $importJobId = $response->json('data.id');

        $this->assertDatabaseHas('import_jobs', [
            'id' => $importJobId,
            'status' => 'pending',
            'filename' => 'contacts.csv',
        ]);

        Queue::assertPushed(ProcessImportJob::class, function ($job) use ($importJobId) {
            return $job->importJob->id === $importJobId;
        });
    }

    public function test_it_returns_progress_for_an_existing_import()
    {
        $user = $this->createUser();
        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'total_rows' => 100,
            'processed_rows' => 50,
            'failed_rows' => 10,
            'status' => 'processing',
        ]);

        $response = $this->getJson("/api/import/{$importJob->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.progress_pct', 60)
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_it_detects_duplicate_uploads()
    {
        Storage::fake('local');
        $user = $this->createUser();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        $fileContent = "first_name,last_name\nJohn,Doe";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $fileContent);
        $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file,
        ])->assertStatus(201);

        $file2 = UploadedFile::fake()->createWithContent('contacts.csv', $fileContent);
        
        $response = $this->postJson('/api/import', [
            'vault_id' => $vault->id,
            'file' => $file2,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.message.0', 'This file has already been uploaded for this vault.');
    }

    public function test_it_cancels_an_import()
    {
        $user = $this->createUser();
        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->deleteJson("/api/import/{$importJob->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Import job cancelled');

        $this->assertDatabaseHas('import_jobs', [
            'id' => $importJob->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_it_downloads_errors_as_csv()
    {
        $user = $this->createUser();
        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'filename' => 'contacts.csv',
        ]);

       ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 2,
            'error_message' => 'Missing required name',
        ]);

        $response = $this->get("/api/import/{$importJob->id}/errors");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('row_number,error_message', $content);
        $this->assertStringContainsString('2,"Missing required name"', $content);
    }
}
