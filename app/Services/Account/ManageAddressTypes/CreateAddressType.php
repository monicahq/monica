<?php

namespace App\Services\Account\ManageAddressTypes;

use App\Models\User;
use App\Models\AddressType;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

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

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'address_type_created',
            'objects' => json_encode([
                'name' => $type->name,
            ]),
        ])->onQueue('low');

        return $type;
    }
}
