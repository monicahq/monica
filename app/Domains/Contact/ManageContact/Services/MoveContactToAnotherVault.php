<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Domains\Vault\ManageCompanies\Services\CreateCompany;
use App\Exceptions\NotEnoughPermissionException;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Vault;
use App\Services\BaseService;
use Carbon\Carbon;

class MoveContactToAnotherVault extends BaseService implements ServiceInterface
{
    private array $data;

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
     * Move a contact from one vault to another.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();
        $this->move();
        $this->moveCompanyInformation();
        $this->updateLastEditedDate();

        return $this->contact;
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

    private function move(): void
    {
        $this->contact->vault_id = $this->newVault->id;
        $this->contact->save();
    }

    /**
     * If the contact belongs to a company, we should move the company
     * information to the new vault as well.
     * If the company only has this contact, we should move the company.
     * However, if the company has other contacts, we should copy the company
     * and move the contact to the new company.
     */
    private function moveCompanyInformation(): void
    {
        if ($this->contact->company) {
            if ($this->contact->company->contacts->count() === 1) {
                $this->contact->company->vault_id = (string) $this->newVault->id;
                $this->contact->company->save();
            } else {
                $newCompany = (new CreateCompany)->execute([
                    'account_id' => $this->author->account_id,
                    'author_id' => $this->author->id,
                    'vault_id' => $this->newVault->id,
                    'name' => $this->contact->company->name,
                    'type' => $this->contact->company->type,
                ]);

                $this->contact->company_id = $newCompany->id;
                $this->contact->save();
            }
        }
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
