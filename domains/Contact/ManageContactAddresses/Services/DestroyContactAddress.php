<?php

namespace App\Contact\ManageContactAddresses\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyContactAddress extends BaseService implements ServiceInterface
{
    private Address $address;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'address_id' => 'required|integer|exists:addresses,id',
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
            'vault_must_belong_to_account',
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Delete a contact address.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->address = Address::where('contact_id', $this->contact->id)
            ->findOrFail($data['address_id']);

        $this->address->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
