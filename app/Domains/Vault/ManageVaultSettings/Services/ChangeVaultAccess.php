<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Exceptions\SameUserException;
use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;

class ChangeVaultAccess extends BaseService implements ServiceInterface
{
    private User $user;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'user_id' => 'required|uuid|exists:users,id',
            'permission' => 'required|integer',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
            'vault_must_belong_to_account',
            'author_must_be_vault_manager',
        ];
    }

    /**
     * Change the access type of the given user in the given vault.
     */
    public function execute(array $data): User
    {
        $this->data = $data;
        $this->validate();
        $this->change();

        return $this->user;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        /** @var User */
        $user = $this->account()->users()
            ->findOrFail($this->data['user_id']);
        $this->user = $user;

        if ($this->user->id === $this->author->id) {
            throw new SameUserException;
        }
    }

    private function change(): void
    {
        // We need to get the vault with pivot, $this->vault does not contains it.
        $this->user->vaults()
            ->where('vault_id', $this->vault->id)
            ->first()
            ->pivot
            ->update([
                'permission' => $this->data['permission'],
            ]);
    }
}
