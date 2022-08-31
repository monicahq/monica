<?php

namespace App\Settings\CreateAccount\Services;

use App\Actions\Fortify\PasswordValidationRules;
use App\Interfaces\ServiceInterface;
use App\Jobs\SetupAccount;
use App\Models\Account;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;

class CreateAccount extends BaseService implements ServiceInterface
{
    use PasswordValidationRules;

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
            'password' => $this->passwordRules(false),
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

        $this->account = Account::create([
            'storage_limit_in_mb' => config('monica.default_storage_limit_in_mb'),
        ]);
        $this->addFirstUser();

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
}
