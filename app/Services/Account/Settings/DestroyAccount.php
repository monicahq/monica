<?php

namespace App\Services\Account\Settings;

use App\Services\BaseService;
use App\Models\Account\Account;
use App\Exceptions\StripeException;

class DestroyAccount extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
        ];
    }

    /**
     * Completely delete an account.
     *
     * @param  array  $data
     * @return void
     *
     * @throws StripeException
     */
    public function execute(array $data): void
    {
        $this->validate($data);

        $account = Account::find($data['account_id']);

        $this->destroyDocuments($account);

        $this->destroyPhotos($account);

        $account->delete();
    }

    /**
     * Destroy the documents.
     *
     * @param  Account  $account
     * @return void
     */
    private function destroyDocuments(Account $account)
    {
        app(DestroyAllDocuments::class)->execute([
            'account_id' => $account->id,
        ]);
    }

    /**
     * Destroy the photos.
     *
     * @param  Account  $account
     * @return void
     */
    private function destroyPhotos(Account $account)
    {
        app(DestroyAllPhotos::class)->execute([
            'account_id' => $account->id,
        ]);
    }
}
