<?php

namespace App\Services\Account\Company;

use App\Services\BaseService;
use App\Models\Account\Company;

class DestroyCompany extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'company_id' => 'required|integer|exists:companies,id',
        ];
    }

    /**
     * Destroy a company.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $company = Company::where('account_id', $data['account_id'])
            ->findOrFail($data['company_id']);

        $company->delete();

        return true;
    }
}
