<?php

namespace App\Domains\Settings\ManageContactInformationTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactInformationType;
use App\Services\BaseService;

class UpdateContactInformationType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_information_type_id' => 'required|integer|exists:contact_information_types,id',
            'type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'protocol' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update a contact information type.
     */
    public function execute(array $data): ContactInformationType
    {
        $this->validateRules($data);

        $type = $this->account()->contactInformationTypes()
            ->findOrFail($data['contact_information_type_id']);

        $type->name = $data['name'];
        $type->type = $this->valueOrNull($data, 'type');
        $type->protocol = $this->valueOrNull($data, 'protocol');
        $type->save();

        return $type;
    }
}
