<?php

namespace App\Domains\Settings\ManageAddressTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyAddressType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'address_type_id' => 'required|integer|exists:address_types,id',
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
     * Destroy an address type.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = $this->account()->addressTypes()
            ->findOrFail($data['address_type_id']);

        $type->delete();
    }
}
