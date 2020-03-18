<?php

namespace App\Services\Contact\ContactField;

use App\Services\BaseService;
use App\Models\Contact\ContactField;

class DestroyContactField extends BaseService
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
            'contact_field_id' => 'required|integer|exists:contact_fields,id',
        ];
    }

    /**
     * Destroy an address.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $contactField = ContactField::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_field_id']);

        $contactField->delete();

        return true;
    }
}
