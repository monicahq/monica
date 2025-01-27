<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Vault;
use App\Services\BaseService;
use Carbon\Carbon;

class CopyContactToAnotherVault extends BaseService implements ServiceInterface
{
    private array $data;

    private Contact $newContact;

    private Vault $newVault;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'other_vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Copy a contact from one vault to another.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();
        $this->copy();
        $this->updateLastEditedDate();

        return $this->newContact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->newVault = $this->account()->vaults()
            ->findOrFail($this->data['other_vault_id']);

        $exists = $this->author->vaults()
            ->where('vaults.id', $this->newVault->id)
            ->wherePivot('permission', '<=', Vault::PERMISSION_EDIT)
            ->exists();

        if (! $exists) {
            throw new NotEnoughPermissionException;
        }
    }

    private function copy(): void
    {
        $this->newContact = new Contact;

        $this->newContact = $this->contact->replicate();
        $this->newContact->vault_id = (string) $this->newVault->id;
        $this->newContact->save();
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
