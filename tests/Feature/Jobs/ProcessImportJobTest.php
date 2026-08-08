<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessImportJob;
use App\Models\Contact;
use App\Models\ImportJob;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessImportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_valid_rows_and_updates_progress()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        $csvContent = "first_name,last_name\nJohn,Doe\nJane,Smith\n";
        $filePath = 'imports/test.csv';
        Storage::put($filePath, $csvContent);

        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'file_path' => $filePath,
        ]);

        $job = new ProcessImportJob($importJob);
        $job->handle();

        $importJob->refresh();

        $this->assertEquals('completed', $importJob->status);
        $this->assertEquals(2, $importJob->total_rows);
        $this->assertEquals(2, $importJob->processed_rows);
        $this->assertEquals(0, $importJob->failed_rows);
        $this->assertEquals(2, $importJob->last_processed_row_index);

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_per_row_error_isolation()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        // Second row is missing required name
        $csvContent = "first_name,last_name\nValid,User\n,\nAnother,Valid\n";
        $filePath = 'imports/test_errors.csv';
        Storage::put($filePath, $csvContent);

        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'file_path' => $filePath,
        ]);

        $initialContactCount = Contact::where('vault_id', $vault->id)->count();

        $job = new ProcessImportJob($importJob);
        $job->handle();

        $importJob->refresh();

        $this->assertEquals('completed', $importJob->status);
        $this->assertEquals(3, $importJob->total_rows);
        $this->assertEquals(2, $importJob->processed_rows);
        $this->assertEquals(1, $importJob->failed_rows);

        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $importJob->id,
            'row_number' => 2,
        ]);
        
        $this->assertEquals($initialContactCount + 2, Contact::where('vault_id', $vault->id)->count());
    }
    
    public function test_retry_and_duplicate_protection()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        // 3 rows total
        $csvContent = "first_name,last_name\nOne,User\nTwo,User\nThree,User\n";
        $filePath = 'imports/test_retry.csv';
        Storage::put($filePath, $csvContent);

        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'file_path' => $filePath,
        ]);

        // Simulate that row 1 was already processed and job crashed.
        // The last_processed_row_index would be 1, processed_rows 1.
        $importJob->update([
            'processed_rows' => 1,
            'last_processed_row_index' => 1,
            'total_rows' => 3,
        ]);
        
        // Manually create the first contact to simulate it was already inserted
        Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'One',
            'last_name' => 'User',
        ]);

        $initialContactCount = Contact::where('vault_id', $vault->id)->count();

        $job = new ProcessImportJob($importJob);
        $job->handle(); // This is the retry

        $importJob->refresh();

        $this->assertEquals('completed', $importJob->status);
        $this->assertEquals(3, $importJob->total_rows);
        // It processed the remaining 2 rows, plus the 1 we simulated earlier = 3
        $this->assertEquals(3, $importJob->processed_rows);
        $this->assertEquals(3, $importJob->last_processed_row_index);

        // There should only be 3 contacts in total
        $this->assertEquals($initialContactCount + 2, Contact::where('vault_id', $vault->id)->count());
        $this->assertDatabaseHas('contacts', ['first_name' => 'Two']);
        $this->assertDatabaseHas('contacts', ['first_name' => 'Three']);
    }

    public function test_job_aborts_when_cancelled()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        $fileContent = "first_name,last_name\nJohn,Doe\nJane,Smith";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $fileContent);
        
        $path = $file->store('imports');
        
        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'file_path' => $path,
            'status' => 'cancelled', // Pretend cancelled before running
        ]);
        
        $job = new ProcessImportJob($importJob);
        $job->handle();

        $importJob->refresh();
        $this->assertEquals('cancelled', $importJob->status);
        $this->assertEquals(0, $importJob->processed_rows);
        
        // Let's test cancelling mid-flight by mocking refresh?
        // Actually, just testing that a cancelled job returns early is good enough for basic coverage.
    }
    public function test_rejects_duplicate_email()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);
        
        $emailType = \App\Models\ContactInformationType::factory()->create([
            'account_id' => $user->account_id,
            'type' => 'email',
            'name' => 'Email',
        ]);

        $existingContact = Contact::factory()->create(['vault_id' => $vault->id]);
        \App\Models\ContactInformation::factory()->create([
            'contact_id' => $existingContact->id,
            'type_id' => $emailType->id,
            'data' => 'duplicate@example.com',
        ]);

        $csvContent = "first_name,last_name,email\nValid,User,new@example.com\nDup,User,duplicate@example.com\n";
        $filePath = 'imports/test_dup.csv';
        Storage::put($filePath, $csvContent);

        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'file_path' => $filePath,
        ]);

        $job = new ProcessImportJob($importJob);
        $job->handle();

        $importJob->refresh();

        $this->assertEquals('completed', $importJob->status);
        $this->assertEquals(2, $importJob->total_rows);
        $this->assertEquals(1, $importJob->processed_rows);
        $this->assertEquals(1, $importJob->failed_rows);
        
        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $importJob->id,
            'row_number' => 2,
        ]);
    }

    public function test_rejects_duplicate_phone()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);
        
        $phoneType = \App\Models\ContactInformationType::factory()->create([
            'account_id' => $user->account_id,
            'type' => 'phone',
            'name' => 'Phone',
        ]);

        $existingContact = Contact::factory()->create(['vault_id' => $vault->id]);
        \App\Models\ContactInformation::factory()->create([
            'contact_id' => $existingContact->id,
            'type_id' => $phoneType->id,
            'data' => '555-9999',
        ]);

        $csvContent = "first_name,last_name,phone\nValid,User,555-0000\nDup,User,555-9999\n";
        $filePath = 'imports/test_dup_phone.csv';
        Storage::put($filePath, $csvContent);

        $importJob = ImportJob::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'file_path' => $filePath,
        ]);

        $job = new ProcessImportJob($importJob);
        $job->handle();

        $importJob->refresh();

        $this->assertEquals('completed', $importJob->status);
        $this->assertEquals(2, $importJob->total_rows);
        $this->assertEquals(1, $importJob->processed_rows);
        $this->assertEquals(1, $importJob->failed_rows);
        
        $this->assertDatabaseHas('import_errors', [
            'import_job_id' => $importJob->id,
            'row_number' => 2,
        ]);
    }
}
