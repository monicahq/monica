<?php

namespace App\Domains\Vault\ManageAddresses\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Services\BaseService;

class UpdateAddress extends BaseService implements ServiceInterface
{
    private Address $address;

    private array $data;

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
     * Update an address.
     */
    public function execute(array $data): Address
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->address;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'address_type_id')) {
            $this->account()->addressTypes()
                ->findOrFail($this->data['address_type_id']);
        }

        $this->address = $this->vault->addresses()
            ->findOrFail($this->data['address_id']);
    }

    private function update(): void
    {
        $this->address->address_type_id = $this->valueOrNull($this->data, 'address_type_id');
        $this->address->line_1 = $this->valueOrNull($this->data, 'line_1');
        $this->address->line_2 = $this->valueOrNull($this->data, 'line_2');
        $this->address->city = $this->valueOrNull($this->data, 'city');
        $this->address->province = $this->valueOrNull($this->data, 'province');
        $this->address->postal_code = $this->valueOrNull($this->data, 'postal_code');
        $this->address->country = $this->valueOrNull($this->data, 'country');
        $this->address->latitude = $this->valueOrNull($this->data, 'latitude');
        $this->address->longitude = $this->valueOrNull($this->data, 'longitude');
        $this->address->save();

        $this->geocodeAddress();
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
