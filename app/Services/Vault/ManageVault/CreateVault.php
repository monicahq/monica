<?php

namespace App\Services\Vault\ManageVault;

use App\Models\Vault;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreateVault extends BaseService implements ServiceInterface
{
    public Vault $vault;
    private array $data;

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
            'type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
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
        ];
    }

    /**
     * Create a vault.
     *
     * @param  array  $data
     * @return Vault
     */
    public function execute(array $data): Vault
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->createVault();
        $this->createUserContact();
        $this->log();

        return $this->vault;
    }

    private function createVault(): void
    {
        $this->vault = Vault::create([
            'account_id' => $this->data['account_id'],
            'type' => $this->data['type'],
            'name' => $this->data['name'],
            'description' => $this->valueOrNull($this->data, 'description'),
        ]);
    }

    private function createUserContact(): void
    {
        $contact = Contact::create([
            'vault_id' => $this->vault->id,
            'first_name' => $this->author->first_name,
            'last_name' => $this->author->last_name,
            'can_be_deleted' => false,
        ]);

        $this->vault->users()->save($this->author, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'vault_created',
            'objects' => json_encode([
                'vault_id' => $this->vault->id,
                'vault_name' => $this->vault->name,
            ]),
        ]);
    }
}
