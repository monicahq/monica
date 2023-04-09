<?php

namespace App\Domains\Vault\ManageVaultImportantDateTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDateType;
use App\Services\BaseService;

class UpdateContactImportantDateType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_important_date_type_id' => 'required|integer|exists:contact_important_date_types,id',
            'label' => 'required|string|max:255',
            'internal_type' => 'nullable|string|max:255',
            'can_be_deleted' => 'nullable|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_vault_editor',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Update a contact important date type.
     */
    public function execute(array $data): ContactImportantDateType
    {
        $this->validateRules($data);

        $type = $this->vault->contactImportantDateTypes()
            ->findOrFail($data['contact_important_date_type_id']);

        $type->label = $data['label'];
        $type->can_be_deleted = $this->valueOrTrue($data, 'can_be_deleted');
        $type->internal_type = $this->valueOrNull($data, 'internal_type');
        $type->save();

        return $type;
    }
}
