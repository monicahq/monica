<?php

namespace App\Domains\Settings\ManageAddressTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\AddressType;
use App\Models\User;
use App\Services\BaseService;

class CreateAddressType extends BaseService implements ServiceInterface
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
            'name' => 'required|string|max:255',
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
     * Create an address type.
     *
     * @param  array  $data
     * @return AddressType
     */
    public function execute(array $data): AddressType
    {
        $this->validateRules($data);

        $type = AddressType::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);

        return $type;
    }
}
