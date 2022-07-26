<?php

namespace App\Contact\ManageContact\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;

class DestroyContact extends BaseService implements ServiceInterface
{
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
     * Destroy a contact.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->log();

        if (! $this->contact->can_be_deleted) {
            throw new CantBeDeletedException();
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

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_destroyed',
            'objects' => json_encode([
                'name' => $this->contact->name,
            ]),
        ])->onQueue('low');
    }
}
