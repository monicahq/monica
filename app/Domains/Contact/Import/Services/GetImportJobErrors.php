<?php

namespace App\Domains\Contact\Import\Services;

use App\Models\ImportJob;
use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class GetImportJobErrors extends BaseService implements ServiceInterface
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
            'page'          => 'nullable|integer|min:1',
            'per_page'      => 'nullable|integer|min:1|max:100',
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
            'author_must_be_in_vault',
        ];
    }

    /**
     * Retrieve and paginate errors for the specified import job.
     */
    public function execute(array $data): array
    {
        $importJob = ImportJob::where('account_id', $data['account_id'])
            ->findOrFail($data['import_job_id']);

        $data['vault_id'] = $importJob->vault_id;

        $this->validateRules($data);

        $page = isset($data['page']) ? (int) $data['page'] : 1;
        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 10;
        $perPage = min(max(1, $perPage), 100);

        $errors = $importJob->errors()
            ->orderBy('row_number')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $errors->getCollection()->map(function ($error): array {
                return [
                    'row' => $error->row_number,
                    'row_number' => $error->row_number,
                    'data' => $error->row_data ?? [],
                    'row_data' => $error->row_data ?? [],
                    'message' => $error->error_message,
                    'error_message' => $error->error_message,
                ];
            })->all(),
            'meta' => [
                'current_page' => $errors->currentPage(),
                'last_page' => $errors->lastPage(),
                'per_page' => $errors->perPage(),
                'total' => $errors->total(),
            ],
        ];
    }
}
