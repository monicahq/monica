<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Jobs\ProcessImportBatch;
use App\Interfaces\ServiceInterface;
use App\Models\ImportJob;
use App\Services\BaseService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Sabre\VObject\Splitter\VCard;

class ImportFile extends BaseService implements ServiceInterface
{
    private ImportJob $importJob;

    private array $rows;

    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'file_path' => 'required|string',
            'original_filename' => 'required|string|max:255',
            'file_size' => 'required|integer',
            'content_hash' => 'required|string|max:64',
            'file_type' => 'required|string|in:vcard,csv',
        ];
    }

    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    public function execute(array $data): ImportJob
    {
        $this->validateRules($data);

        if ($data['file_type'] === 'csv') {
            $rows = $this->parseCsv($data['file_path']);
        } else {
            $rows = $this->parseVcf($data['file_path']);
        }

        $this->importJob = ImportJob::create([
            'account_id' => $data['account_id'],
            'vault_id' => $data['vault_id'],
            'user_id' => $data['author_id'],
            'file_path' => $data['file_path'],
            'original_filename' => $data['original_filename'],
            'file_size' => $data['file_size'],
            'content_hash' => $data['content_hash'],
            'file_type' => $data['file_type'],
            'status' => 'pending',
            'total_rows' => count($rows),
        ]);

        $chunks = array_chunk($rows, 50);
        $chunkKey = $data['file_type'] === 'csv' ? 'csv_chunk' : 'vcard_chunk';

        $this->dispatchBatch($chunks, $chunkKey);

        return $this->importJob;
    }

    private function parseCsv(string $filePath): array
    {
        $stream = Storage::readStream($filePath);
        $header = null;
        $rows = [];

        while (($line = fgetcsv($stream, 0, ',')) !== false) {
            $line = array_map('trim', $line);

            if (array_filter($line) === []) {
                continue;
            }

            if ($header === null) {
                $header = $line;

                continue;
            }

            if (count($line) === count($header)) {
                $rows[] = array_combine($header, $line);
            }
        }

        fclose($stream);

        if (empty($header)) {
            Storage::delete($filePath);
            throw new \InvalidArgumentException('CSV file has no header row.');
        }

        if (empty($rows)) {
            Storage::delete($filePath);
            throw new \InvalidArgumentException('No data rows found in the CSV file.');
        }

        $indexedRows = [];
        foreach ($rows as $i => $row) {
            $indexedRows[] = ['number' => $i + 1, 'data' => $row];
        }

        return $indexedRows;
    }

    private function parseVcf(string $filePath): array
    {
        $content = Storage::get($filePath);

        $splitter = new VCard($content);
        $vcards = [];
        while ($vcard = $splitter->getNext()) {
            $vcards[] = $vcard->serialize();
        }

        if (empty($vcards)) {
            Storage::delete($filePath);
            throw new \InvalidArgumentException('No valid vCards found in the file.');
        }

        return $vcards;
    }

    private function dispatchBatch(array $chunks, string $chunkKey): void
    {
        $batch = Bus::batch([]);

        foreach ($chunks as $chunk) {
            $batch->add(new ProcessImportBatch([
                'import_job_id' => $this->importJob->id,
                $chunkKey => $chunk,
                'account_id' => $this->importJob->account_id,
                'author_id' => $this->importJob->user_id,
                'vault_id' => $this->importJob->vault_id,
            ]));
        }

        $this->importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $importJob = $this->importJob;

        $batch->then(function () use ($importJob) {
            $importJob->refresh();
            if ($importJob->status === 'cancelled') {
                return;
            }
            if ($importJob->processed_rows === 0 && $importJob->total_rows > 0) {
                $importJob->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                ]);
            } else {
                $importJob->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        })->catch(function () use ($importJob) {
            $importJob->refresh();
            if ($importJob->status === 'cancelled') {
                return;
            }
            $importJob->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
        })->onQueue('imports');

        $batchInstance = $batch->dispatch();

        $this->importJob->update([
            'batch_id' => $batchInstance->id,
        ]);
    }
}
