<?php

namespace App\Domains\Settings\ManageContactInformationTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactInformationType;
use App\Services\BaseService;

class CreateContactInformationType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'type' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'name_translation_key' => 'nullable|string|max:255',
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
     * Create a contact information type.
     */
    public function execute(array $data): ContactInformationType
    {
        $this->validateRules($data);

        return ContactInformationType::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'] ?? null,
            'name_translation_key' => $data['name_translation_key'] ?? null,
            'type' => $this->valueOrNull($data, 'type'),
            'protocol' => $this->valueOrNull($data, 'protocol'),
        ]);
    }
}
