<?php

namespace App\Domains\Settings\ManageAddressTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\AddressType;
use App\Services\BaseService;

class CreateAddressType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'name' => 'nullable|string|max:255',
            'name_translation_key' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
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
     * Create an address type.
     */
    public function execute(array $data): AddressType
    {
        $this->validateRules($data);

        $type = AddressType::create([
            'account_id' => $data['account_id'],
            'name' => $this->valueOrNull($data, 'name'),
            'name_translation_key' => $this->valueOrNull($data, 'name_translation_key'),
            'type' => $this->valueOrNull($data, 'type') ?? 'custom',
        ]);

        return $type;
    }
}
