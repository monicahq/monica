<?php

namespace App\Domains\Contact\Import\Services;

use App\Enums\ImportJobStatus;
use App\Interfaces\ServiceInterface;
use App\Models\ImportJob;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CancelImportJob extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id'    => 'required|uuid|exists:accounts,id',
            'author_id'     => 'required|uuid|exists:users,id',
            'import_job_id' => 'required|integer|exists:import_jobs,id',
            'vault_id'      => 'required|uuid',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Cancel a running or pending import job and enforce vault editor permissions.
     */
    public function execute(array $data): ImportJob
    {
        return DB::transaction(function () use ($data): ImportJob {
            $importJob = ImportJob::where('account_id', $data['account_id'])
                ->lockForUpdate()
                ->findOrFail($data['import_job_id']);

            $data['vault_id'] = $importJob->vault_id;

            $this->validateRules($data);

            if ($importJob->status === ImportJobStatus::PENDING) {
                $importJob->update([
                    'status'             => ImportJobStatus::CANCELLED,
                    'cancelled_at'       => now(),
                    'completed_at'       => now(),
                    'last_heartbeat_at'  => now(),
                ]);

                if ($importJob->file_path) {
                    Storage::disk('local')->delete($importJob->file_path);
                }
            } elseif ($importJob->status === ImportJobStatus::PROCESSING) {
                $importJob->update([
                    'status'             => ImportJobStatus::CANCELLING,
                    'cancelled_at'       => now(),
                    'last_heartbeat_at'  => now(),
                ]);
            }

            return $importJob;
        });
    }
}
