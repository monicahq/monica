<?php

namespace App\Domains\Contact\Import\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use App\Models\ImportJob;
use Illuminate\Pagination\LengthAwarePaginator;

class ListImportJobs extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id'  => 'required|uuid|exists:users,id',
            'vault_id'   => 'nullable|uuid',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * List recent import jobs for the authenticated user's account,
     * filtered by vaults they have access to.
     */
    public function execute(array $data): LengthAwarePaginator
    {
        $this->validateRules($data);

        $vaultIds = $this->author->vaults()->pluck('vaults.id');

        if (! empty($data['vault_id'])) {
            if (! $vaultIds->contains($data['vault_id'])) {
                throw new \App\Exceptions\NotEnoughPermissionException;
            }
            $vaultIds = collect([$data['vault_id']]);
        }

        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 10;
        $perPage = min(max(1, $perPage), 100);

        return ImportJob::where('account_id', $data['account_id'])
            ->whereIn('vault_id', $vaultIds)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
