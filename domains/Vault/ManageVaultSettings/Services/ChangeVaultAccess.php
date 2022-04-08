<?php

namespace App\Vault\ManageVaultSettings\Services;

use App\Models\User;
use App\Helpers\VaultHelper;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ServiceInterface;
use App\Exceptions\SameUserException;

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

        $this->log();

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

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'vault_access_permission_changed',
            'objects' => json_encode([
                'user_name' => $this->user->name,
                'vault_name' => $this->vault->name,
                'permission_type' => VaultHelper::getPermissionFriendlyName($this->data['permission']),
            ]),
        ])->onQueue('low');
    }
}
