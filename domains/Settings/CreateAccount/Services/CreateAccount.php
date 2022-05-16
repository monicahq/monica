<?php

namespace App\Settings\CreateAccount\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\SetupAccount;
use App\Models\Account;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;

class CreateAccount extends BaseService implements ServiceInterface
{
    private User $user;
    private Account $account;
    private array $data;

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
        $this->addLogs();

        SetupAccount::dispatch($this->user)->onQueue('high');

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
            'timezone' => 'UTC',
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
        ])->onQueue('low');
    }
}
