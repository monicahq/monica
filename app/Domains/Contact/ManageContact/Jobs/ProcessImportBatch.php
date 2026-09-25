<?php

namespace App\Domains\Contact\ManageContact\Jobs;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContact\Services\CsvToVCard;
use App\Interfaces\ServiceInterface;
use App\Models\ImportJob;
use App\Services\QueuableService;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ProcessImportBatch extends QueuableService implements ServiceInterface
{
    use Batchable;

    public function rules(): array
    {
        return [
            'import_job_id' => 'required|string|exists:import_jobs,id',
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
        ];
    }

    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'author_must_be_vault_editor',
        ];
    }

    public function execute(array $data): void
    {
        $this->validateRules($data);

        $importJob = ImportJob::findOrFail($data['import_job_id']);

        if ($importJob->status === 'cancelled') {
            return;
        }

        if ($importJob->status !== 'processing') {
            $importJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
        }

        if (isset($data['vcard_chunk'])) {
            $this->processVCardChunk($importJob, $data);
        } elseif (isset($data['csv_chunk'])) {
            $this->processCsvChunk($importJob, $data);
        }
    }

    private function processVCardChunk(ImportJob $importJob, array $data): void
    {
        foreach ($data['vcard_chunk'] as $vcardContent) {
            if ($this->batch() && $this->batch()->cancelled()) {
                break;
            }

            if ($importJob->fresh()->status === 'cancelled') {
                break;
            }

            $this->importSingleVCard($importJob, $data, $vcardContent);
        }
    }

    private function processCsvChunk(ImportJob $importJob, array $data): void
    {
        foreach ($data['csv_chunk'] as $item) {
            if ($this->batch() && $this->batch()->cancelled()) {
                break;
            }

            if ($importJob->fresh()->status === 'cancelled') {
                break;
            }

            $rowNumber = $item['number'];
            $row = $item['data'];

            $validationErrors = CsvToVCard::validate($row);
            if (! empty($validationErrors)) {
                $importJob->increment('skipped_rows');
                $this->addError($importJob, $rowNumber, implode('; ', $validationErrors));

                continue;
            }

            $vcardContent = CsvToVCard::convert($row);
            $this->importSingleVCard($importJob, $data, $vcardContent);
        }
    }

    private function importSingleVCard(ImportJob $importJob, array $data, string $vcardContent): void
    {
        try {
            $result = app(ImportVCard::class)->execute([
                'account_id' => $data['account_id'],
                'author_id' => $data['author_id'],
                'vault_id' => $data['vault_id'],
                'entry' => $vcardContent,
                'behaviour' => ImportVCard::BEHAVIOUR_ADD,
            ]);

            if (! isset($result['error'])) {
                $contactId = $result['id'];
                $importJob->increment('processed_rows');

                $createdIds = $importJob->contact_ids_created ?? [];
                $createdIds[] = $contactId;
                $importJob->update(['contact_ids_created' => $createdIds]);
            } else {
                $importJob->increment('failed_rows');
            }
        } catch (\Exception $e) {
            Log::channel('database')->error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'import_job_id' => $data['import_job_id'],
                $e,
            ]);

            $importJob->increment('failed_rows');

            $errorLog = $importJob->error_log ?? '';
            $errorLog .= '['.now().'] '.$e->getMessage()."\n";
            $importJob->update(['error_log' => $errorLog]);
        }
    }

    private function addError(ImportJob $importJob, int $rowNumber, string $message): void
    {
        $errors = $importJob->errors ?? [];
        $errors[] = ['row' => $rowNumber, 'message' => $message];
        $importJob->errors = $errors;
        $importJob->saveQuietly();
    }
}
