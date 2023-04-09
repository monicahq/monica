<?php

namespace App\Domains\Settings\ManageAddressTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\AddressType;
use App\Services\BaseService;

class UpdateAddressType extends BaseService implements ServiceInterface
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
            'name' => 'required|string|max:255',
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
     * Update an address type.
     */
    public function execute(array $data): AddressType
    {
        $this->validateRules($data);

        $type = $this->account()->addressTypes()
            ->findOrFail($data['address_type_id']);

        $type->name = $data['name'];
        $type->save();

        return $type;
    }
}
