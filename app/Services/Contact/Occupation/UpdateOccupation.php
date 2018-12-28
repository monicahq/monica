<?php

namespace App\Services\Contact\Occupation;

use App\Services\BaseService;
use App\Models\Contact\Occupation;

class UpdateOccupation extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'company_id' => 'required|integer|exists:companies,id',
            'occupation_id' => 'required|integer|exists:occupations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'salary' => 'nullable|integer',
            'salary_unit' => 'nullable|integer',
            'currently_works_here' => 'nullable|boolean',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    /**
     * Update a occupation.
     *
     * @param array $data
     * @return Occupation
     */
    public function execute(array $data) : Occupation
    {
        $this->validate($data);

        $occupation = Occupation::where('account_id', $data['account_id'])
            ->where('contact_id', $data['contact_id'])
            ->where('company_id', $data['company_id'])
            ->findOrFail($data['occupation_id']);

        $occupation->update([
            'title' => $data['title'],
            'description' => $this->nullOrValue($data, 'description'),
            'salary' => $this->nullOrValue($data, 'salary'),
            'salary_unit' => $this->nullOrValue($data, 'salary_unit'),
            'currently_works_here' => $this->nullOrValue($data, 'currently_works_here'),
            'start_date' => $this->nullOrValue($data, 'start_date'),
            'end_date' => $this->nullOrValue($data, 'end_date'),
        ]);

        return $occupation;
    }
}
