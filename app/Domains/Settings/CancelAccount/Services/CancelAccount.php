<?php

namespace App\Domains\Settings\CancelAccount\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Account;
use App\Models\File;
use App\Services\QueuableService;

class CancelAccount extends QueuableService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
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
        ];
    }

    /**
     * Delete an account.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $account = Account::findOrFail($data['account_id']);
        $this->destroyAllFiles($account);

        $account->delete();
    }

    private function destroyAllFiles(Account $account): void
    {
        $vaultIds = $account->vaults()->select('id')->get()->toArray();

        File::whereIn('vault_id', $vaultIds)->chunk(100, function ($files) {
            $files->each(function ($file) {
                $file->delete();
            });
        });
    }
}
