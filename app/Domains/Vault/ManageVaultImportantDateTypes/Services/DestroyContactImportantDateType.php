<?php

namespace App\Domains\Vault\ManageVaultImportantDateTypes\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyContactImportantDateType extends BaseService implements ServiceInterface
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
     * Destroy a contact important date type.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = $this->vault->contactImportantDateTypes()
            ->findOrFail($data['contact_important_date_type_id']);

        if (! $type->can_be_deleted) {
            throw new CantBeDeletedException;
        }

        $type->delete();
    }
}
