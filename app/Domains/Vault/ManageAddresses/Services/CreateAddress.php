<?php

namespace App\Domains\Vault\ManageAddresses\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Services\BaseService;

class CreateAddress extends BaseService implements ServiceInterface
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
            'address_type_id' => 'nullable|integer|exists:address_types,id',
            'line_1' => 'nullable|string|max:255',
            'line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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
     * Create an address.
     */
    public function execute(array $data): Address
    {
        $this->validateRules($data);

        if ($this->valueOrNull($data, 'address_type_id')) {
            $this->account()->addressTypes()
                ->findOrFail($data['address_type_id']);
        }

        $this->address = Address::create([
            'vault_id' => $data['vault_id'],
            'address_type_id' => $this->valueOrNull($data, 'address_type_id'),
            'line_1' => $this->valueOrNull($data, 'line_1'),
            'line_2' => $this->valueOrNull($data, 'line_2'),
            'city' => $this->valueOrNull($data, 'city'),
            'province' => $this->valueOrNull($data, 'province'),
            'postal_code' => $this->valueOrNull($data, 'postal_code'),
            'country' => $this->valueOrNull($data, 'country'),
            'latitude' => $this->valueOrNull($data, 'latitude'),
            'longitude' => $this->valueOrNull($data, 'longitude'),
        ]);

        $this->geocodeAddress();

        return $this->address;
    }

    private function geocodeAddress(): void
    {
        if (config('monica.location_iq_api_key')) {
            GetGPSCoordinate::dispatch([
                'address_id' => $this->address->id,
            ])->onQueue('low');
        }
    }
}
