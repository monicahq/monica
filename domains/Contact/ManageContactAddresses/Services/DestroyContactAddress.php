<?php

namespace App\Contact\ManageContactAddresses\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\Address;
use App\Models\AddressType;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyContactAddress extends BaseService implements ServiceInterface
{
    private Address $address;

    private AddressType $addressType;

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

        $this->log();
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_address_destroyed',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_address_destroyed',
            'objects' => json_encode([
            ]),
        ])->onQueue('low');
    }
}
