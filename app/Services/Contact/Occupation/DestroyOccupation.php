<?php

namespace App\Services\Contact\Occupation;

use App\Services\BaseService;
use App\Models\Contact\Occupation;

class DestroyOccupation extends BaseService
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
            'occupation_id' => 'required|integer|exists:occupations,id',
        ];
    }

    /**
     * Destroy an occupation.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $occupation = Occupation::where('account_id', $data['account_id'])
            ->findOrFail($data['occupation_id']);

        $occupation->delete();

        return true;
    }
}
