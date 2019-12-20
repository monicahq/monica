<?php

namespace App\Services\Account\CustomField;

use App\Services\BaseService;
use App\Models\Account\CustomField;

class UpdateCustomField extends BaseService
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
            'custom_field_id' => 'required|integer|exists:custom_fields,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Update a custom field.
     *
     * @param array $data
     * @return CustomField
     */
    public function execute(array $data): CustomField
    {
        $this->validate($data);

        $customField = CustomField::where('account_id', $data['account_id'])
            ->findOrFail($data['custom_field_id']);

        $customField->update([
            'name' => $data['name'],
        ]);

        return $customField;
    }
}
