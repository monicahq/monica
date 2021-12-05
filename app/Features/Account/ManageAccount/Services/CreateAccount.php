<?php

namespace App\Features\Account\ManageAccount\Services;

use App\Models\User;
use App\Models\Vault;
use App\Models\Account;
use App\Jobs\SetupAccount;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ServiceInterface;
use Illuminate\Support\Facades\Hash;

class CreateAccount extends BaseService implements ServiceInterface
{
    private User $user;
    private Account $account;
    private array $data;
    public Vault $vault;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|unique:users,email|email|max:255',
            'password' => 'required|alpha_dash|string|max:255',
        ];
    }

    /**
     * Create an account.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->data = $data;
        $this->validateRules($this->data);

        $this->account = Account::create();
        $this->addFirstUser();
        $this->createFirstVault();
        $this->addLogs();

        SetupAccount::dispatch($this->user)->onQueue('low');

        return $this->user;
    }

    private function addFirstUser(): void
    {
        $this->user = User::create([
            'account_id' => $this->account->id,
            'first_name' => $this->data['first_name'],
            'last_name' => $this->data['last_name'],
            'email' => $this->data['email'],
            'password' => Hash::make($this->data['password']),
            'is_account_administrator' => true,
        ]);
    }

    private function createFirstVault(): void
    {
        $this->vault = Vault::create([
            'account_id' => $this->account->id,
            'type' => Vault::TYPE_PERSONAL,
            'name' => trans('account.default_vault_name'),
        ]);

        DB::table('user_vault')->insert([
            'vault_id' => $this->vault->id,
            'user_id' => $this->user->id,
            'permission' => Vault::PERMISSION_MANAGE,
        ]);
    }

    private function addLogs(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->account->id,
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'action_name' => 'account_created',
            'objects' => json_encode([]),
        ]);

        CreateAuditLog::dispatch([
            'account_id' => $this->account->id,
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'action_name' => 'vault_created',
            'objects' => json_encode([
                'vault_id' => $this->vault->id,
                'vault_name' => $this->vault->name,
            ]),
        ]);
    }
}
