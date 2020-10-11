<?php

namespace App\Services\Contact\ContactField;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use App\Services\Contact\Label\UpdateContactFieldLabels;

class CreateContactField extends BaseService
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
            'contact_field_type_id' => 'required|integer|exists:contact_field_types,id',
            'data' => 'required|string|max:255',
            'labels' => 'nullable|array',
        ];
    }

    /**
     * Create a contact field.
     *
     * @param array $data
     * @return ContactField
     */
    public function execute(array $data): ContactField
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        ContactFieldType::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_field_type_id']);

        $contactField = ContactField::create([
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'contact_field_type_id' => $data['contact_field_type_id'],
            'data' => $this->nullOrValue($data, 'data'),
        ]);

        if ($labels = $this->nullOrValue($data, 'labels')) {
            app(UpdateContactFieldLabels::class)->execute([
                'account_id' => $data['account_id'],
                'contact_field_id' => $contactField->id,
                'labels' => $labels,
            ]);
        }

        return $contactField;
    }
}
