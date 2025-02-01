<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Services\QueuableService;

class DestroyContact extends QueuableService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
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
     * Destroy a contact.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        if (! $this->contact->can_be_deleted) {
            throw new CantBeDeletedException;
        }

        $this->destroyFiles();

        $this->contact->delete();
    }

    private function destroyFiles(): void
    {
        $files = $this->contact->files;
        foreach ($files as $file) {
            $file->delete();
        }
    }
}
