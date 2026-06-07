<?php

namespace App\Domains\Contact\Import\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use App\Models\ImportJob;

class GetImportJob extends BaseService implements ServiceInterface
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
            'author_must_be_in_vault',
        ];
    }

    /**
     * Retrieve the import job and enforce vault view permissions.
     */
    public function execute(array $data): ImportJob
    {
        $importJob = ImportJob::where('account_id', $data['account_id'])
            ->findOrFail($data['import_job_id']);

        $data['vault_id'] = $importJob->vault_id;

        $this->validateRules($data);

        return $importJob;
    }
}
