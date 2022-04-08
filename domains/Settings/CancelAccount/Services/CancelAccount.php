<?php

namespace App\Settings\CancelAccount\Services;

use App\Models\User;
use App\Models\Account;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CancelAccount extends BaseService implements ServiceInterface
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
            'author_id' => 'required|integer|exists:users,id',
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
        ];
    }

    /**
     * Delete an account.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $account = Account::findOrFail($data['account_id']);
        $account->delete();
    }
}
