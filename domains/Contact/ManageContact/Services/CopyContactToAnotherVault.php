<?php

namespace App\Contact\ManageContact\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\Contact;
use App\Models\Vault;
use App\Services\BaseService;

class CopyContactToAnotherVault extends BaseService implements ServiceInterface
{
    private array $data;
    private Contact $newContact;
    private Vault $newVault;

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
            'other_vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
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
     * Copy a contact from one vault to another.
     *
     * @param  array  $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();
        $this->copy();
        $this->log();

        return $this->newContact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->newVault = Vault::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['other_vault_id']);

        $exists = $this->author->vaults()
            ->where('vaults.id', $this->newVault->id)
            ->wherePivot('permission', '<=', Vault::PERMISSION_EDIT)
            ->exists();

        if (! $exists) {
            throw new NotEnoughPermissionException();
        }
    }

    private function copy(): void
    {
        $this->newContact = new Contact();

        $this->newContact = $this->contact->replicate();
        $this->newContact->vault_id = $this->newVault->id;
        $this->newContact->save();
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_copied_to_another_vault',
            'objects' => json_encode([
                'original_vault_name' => $this->vault->name,
                'target_vault_name' => $this->newVault->name,
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_copied_to_another_vault',
            'objects' => json_encode([
                'name' => $this->newVault->name,
            ]),
        ])->onQueue('low');
    }
}
