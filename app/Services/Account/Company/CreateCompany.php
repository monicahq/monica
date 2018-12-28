<?php

namespace App\Services\Account\Company;

use App\Services\BaseService;
use App\Models\Account\Company;

class CreateCompany extends BaseService
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
            'name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'number_of_employees' => 'nullable|integer',
        ];
    }

    /**
     * Create a company.
     *
     * @param array $data
     * @return Company
     */
    public function execute(array $data) : Company
    {
        $this->validate($data);

        return Company::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'website' => $this->nullOrValue($data, 'website'),
            'number_of_employees' => $this->nullOrValue($data, 'number_of_employees'),
        ]);
    }
}
