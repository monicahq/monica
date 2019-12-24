<?php

namespace App\Services\Family\Family;

use App\Models\Family\Family;
use App\Services\BaseService;

class DestroyFamily extends BaseService
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
            'family_id' => 'required|integer|exists:families,id',
        ];
    }

    /**
     * Destroy a family.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $family = Family::where('account_id', $data['account_id'])
            ->findOrFail($data['family_id']);

        $family->delete();

        return true;
    }
}
