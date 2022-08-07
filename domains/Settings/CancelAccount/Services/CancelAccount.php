<?php

namespace App\Settings\CancelAccount\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Services\BaseService;

class CancelAccount extends BaseService implements ServiceInterface
{
    private Account $account;

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

        $this->account = Account::findOrFail($data['account_id']);
        $this->destroyAllFiles();

        $this->account->delete();
    }

    private function destroyAllFiles(): void
    {
        $vaultIds = $this->account->vaults()->select('id')->get()->toArray();
        $contactIds = Contact::whereIn('vault_id', $vaultIds)->select('id')->get()->toArray();

        File::whereIn('contact_id', $contactIds)->chunk(100, function ($files) {
            $files->each(function ($file) {
                $file->delete();
            });
        });
    }
}
