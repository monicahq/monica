<?php

namespace App\Domains\Settings\ManageAddressTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\AddressType;
use App\Models\User;
use App\Services\BaseService;

class DestroyAddressType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'address_type_id' => 'required|integer|exists:address_types,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = AddressType::where('account_id', $data['account_id'])
            ->findOrFail($data['address_type_id']);

        $type->delete();
    }
}
