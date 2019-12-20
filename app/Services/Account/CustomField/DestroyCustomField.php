<?php

namespace App\Services\Account\CustomField;

use App\Models\Account\CustomField;
use App\Services\BaseService;
use App\Models\Contact\Gender;

class DestroyCustomField extends BaseService
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
        ];
    }

    /**
     * Destroy a custom field.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $customField = CustomField::where('account_id', $data['account_id'])
            ->findOrFail($data['custom_field_id']);

        $customField->delete();

        return true;
    }
}
