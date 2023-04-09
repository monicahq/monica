<?php

namespace App\Domains\Vault\ManageAddresses\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Services\BaseService;

class DestroyAddress extends BaseService implements ServiceInterface
{
    private Address $address;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'address_id' => 'required|integer|exists:addresses,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Delete an address.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->address = $this->vault->addresses()
            ->findOrFail($data['address_id']);

        $this->address->delete();
    }
}
