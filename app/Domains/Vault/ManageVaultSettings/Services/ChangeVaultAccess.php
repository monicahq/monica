<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Exceptions\SameUserException;
use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class ChangeVaultAccess extends BaseService implements ServiceInterface
{
    private User $user;

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
            'vault_id' => 'required|integer|exists:vaults,id',
            'user_id' => 'required|integer|exists:users,id',
            'permission' => 'required|integer',
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
            'author_must_be_account_administrator',
            'vault_must_belong_to_account',
            'author_must_be_vault_manager',
        ];
    }

    /**
     * Change the access type of the given user in the given vault.
     *
     * @param  array  $data
     * @return User
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

        $this->user = User::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['user_id']);

        if ($this->user->id === $this->author->id) {
            throw new SameUserException();
        }
    }

    private function change(): void
    {
        DB::table('user_vault')
            ->where('vault_id', $this->vault->id)
            ->where('user_id', $this->user->id)
            ->update(['permission' => $this->data['permission']]);
    }
}
