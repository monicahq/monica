<?php

namespace App\Jobs;

use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Domains\Contact\ManageContactInformation\Services\CreateContactInformation;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\ImportError;
use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importJob;
    public $timeout = 3600; 
    public $tries = 3; 

    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
    }

    public function handle(): void
    {
        ini_set('auto_detect_line_endings', true);

        $this->importJob->refresh();
        if ($this->importJob->status === 'cancelled') {
            return;
        }

        $this->importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $filePath = Storage::path($this->importJob->file_path);
        
        if (!file_exists($filePath)) {
            $this->failJob("File not found at path: {$this->importJob->file_path}");
            return;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);
        if (!$header) {
            $this->failJob("Invalid or empty CSV file.");
            fclose($file);
            return;
        }

        $header = array_map('strtolower', array_map('trim', $header));
        $firstNameIndex = array_search('first_name', $header);
        $lastNameIndex = array_search('last_name', $header);
        $emailIndex = array_search('email', $header); 
        $phoneIndex = array_search('phone', $header);

        $lastProcessedIndex = $this->importJob->last_processed_row_index;
        $currentRowIndex = 0;
        
        if ($this->importJob->total_rows == 0) {
            $totalRows = 0;
            while (fgetcsv($file) !== false) {
                $totalRows++;
            }
            $this->importJob->update(['total_rows' => $totalRows]);
            rewind($file);
            fgetcsv($file);
        }
        
        while ($currentRowIndex < $lastProcessedIndex && fgetcsv($file) !== false) {
            $currentRowIndex++;
        }

        $chunkSize = 50;
        $chunk = [];

        while (($row = fgetcsv($file)) !== false) {
            $currentRowIndex++;
            $chunk[] = [
                'index' => $currentRowIndex,
                'data' => $row
            ];

            if (count($chunk) === $chunkSize) {
                $this->importJob->refresh();
                if ($this->importJob->status === 'cancelled') {
                    fclose($file);
                    return;
                }
                
                $this->processChunk($chunk, $header, $firstNameIndex, $lastNameIndex, $emailIndex, $phoneIndex);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $this->importJob->refresh();
            if ($this->importJob->status !== 'cancelled') {
                $this->processChunk($chunk, $header, $firstNameIndex, $lastNameIndex, $emailIndex, $phoneIndex);
            }
        }

        fclose($file);
        $this->importJob->refresh();
        if ($this->importJob->status === 'cancelled') {
            return;
        }

        if ($this->importJob->processed_rows == 0 && $this->importJob->failed_rows > 0) {
            $this->importJob->update([
                'status' => 'failed',
                'failure_message' => 'No contact was imported successfully.',
                'completed_at' => now(),
            ]);
        } else {
            $this->importJob->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    private function processChunk(array $chunk, array $header, $firstNameIndex, $lastNameIndex, $emailIndex, $phoneIndex): void
    {
        $emailType = ContactInformationType::where('account_id', $this->importJob->account_id)
            ->where('type', 'email')
            ->first();

        $phoneType = ContactInformationType::where('account_id', $this->importJob->account_id)
            ->where('type', 'phone')
            ->first();

        DB::transaction(function () use ($chunk, $header, $firstNameIndex, $lastNameIndex, $emailIndex, $emailType, $phoneIndex, $phoneType) {
            $processedCount = 0;
            $failedCount = 0;
            $lastIndex = 0;
            foreach ($chunk as $rowItem) {
                $rowIndex = $rowItem['index'];
                $row = $rowItem['data'];
                $lastIndex = $rowIndex;

                try {
                    $firstName = $firstNameIndex !== false ? (isset($row[$firstNameIndex]) ? trim($row[$firstNameIndex]) : null) : null;
                    $lastName = $lastNameIndex !== false ? (isset($row[$lastNameIndex]) ? trim($row[$lastNameIndex]) : null) : null;
                    if (empty($firstName) && empty($lastName)) {
                        throw new \Exception("Missing required name (first_name or last_name must be provided).");
                    }
                    
                    $email = null;
                    if ($emailIndex !== false && isset($row[$emailIndex])) {
                        $email = trim($row[$emailIndex]);
                        if (!empty($email) && $emailType) {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                throw new \Exception("Invalid email format: {$email}");
                            }

                            $emailExists = ContactInformation::where('type_id', $emailType->id)
                                ->where('data', $email)
                                ->whereHas('contact', function ($q) {
                                    $q->where('vault_id', $this->importJob->vault_id);
                                })->exists();

                            if ($emailExists) {
                                throw new \Exception("A contact with email {$email} already exists in this vault.");
                            }
                        }
                    }

                    $phone = null;
                    if ($phoneIndex !== false && isset($row[$phoneIndex])) {
                        $phone = trim($row[$phoneIndex]);
                        if (!empty($phone) && $phoneType) {
                            if (!preg_match('/^[0-9\-\+\(\)\s]+$/', $phone)) {
                                throw new \Exception("Invalid phone number format: {$phone}");
                            }

                            $phoneExists = ContactInformation::where('type_id', $phoneType->id)
                                ->where('data', $phone)
                                ->whereHas('contact', function ($q) {
                                    $q->where('vault_id', $this->importJob->vault_id);
                                })->exists();

                            if ($phoneExists) {
                                throw new \Exception("A contact with phone number {$phone} already exists in this vault.");
                            }
                        }
                    }
                    
                    $data = [
                        'account_id' => $this->importJob->account_id,
                        'author_id' => $this->importJob->user_id,
                        'vault_id' => $this->importJob->vault_id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'listed' => true,
                    ];
                    
                    $contact = (new CreateContact)->execute($data);

                    if (!empty($email) && $emailType) {
                            
                            (new CreateContactInformation)->execute([
                                'account_id' => $this->importJob->account_id,
                                'vault_id' => $this->importJob->vault_id,
                                'author_id' => $this->importJob->user_id,
                                'contact_id' => $contact->id,
                                'contact_information_type_id' => $emailType->id,
                                'data' => $email,
                            ]);
                    }

                    if ($phoneIndex !== false && isset($row[$phoneIndex])) {
                        $phone = trim($row[$phoneIndex]);
                        if (!empty($phone) && $phoneType) {
                            (new CreateContactInformation)->execute([
                                'account_id' => $this->importJob->account_id,
                                'vault_id' => $this->importJob->vault_id,
                                'author_id' => $this->importJob->user_id,
                                'contact_id' => $contact->id,
                                'contact_information_type_id' => $phoneType->id,
                                'data' => $phone,
                            ]);
                        }
                    }
                    
                    $processedCount++;
                } catch (ValidationException $e) {
                    $failedCount++;
                    $error = "Validation error: " . collect($e->errors())->flatten()->implode(', ');
                    ImportError::create([
                        'import_job_id' => $this->importJob->id,
                        'row_number' => $rowIndex,
                        'error_message' => $error,
                    ]);
                } catch (Throwable $e) {
                    $failedCount++;
                    ImportError::create([
                        'import_job_id' => $this->importJob->id,
                        'row_number' => $rowIndex,
                        'error_message' => get_class($e) . ': ' . substr($e->getMessage(), 0, 500), // Avoid text overflow
                    ]);
                }
            }

            $this->importJob->increment('processed_rows', $processedCount);
            $this->importJob->increment('failed_rows', $failedCount);
            $this->importJob->update(['last_processed_row_index' => $lastIndex]);
        });
    }

    private function failJob(string $message): void
    {
        $this->importJob->update([
            'status' => 'failed',
            'failure_message' => $message,
            'completed_at' => now(),
        ]);
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->importJob->update([
            'status' => 'failed',
            'failure_message' => 'System error: ' . substr($exception->getMessage(), 0, 500),
            'completed_at' => now(),
        ]);
    }
}
