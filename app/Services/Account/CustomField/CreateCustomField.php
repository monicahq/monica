<?php

namespace App\Services\Account\CustomField;

use App\Models\Account\CustomField;
use App\Services\BaseService;
use App\Models\Contact\Gender;
use Illuminate\Validation\Rule;

class CreateCustomField extends BaseService
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
        ];
    }

    /**
     * Create a custom field.
     *
     * @param array $data
     * @return CustomField
     */
    public function execute(array $data): CustomField
    {
        $this->validate($data);

        return CustomField::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);
    }
}
