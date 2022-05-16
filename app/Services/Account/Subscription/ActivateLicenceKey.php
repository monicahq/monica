<?php

namespace App\Services\Account\Subscription;

use Exception;
use App\Services\BaseService;
use App\Models\Account\Account;
use App\Services\QueuableService;
use App\Services\DispatchableService;
use App\Exceptions\NoLicenceKeyEncryptionSetException;

class ActivateLicenceKey extends BaseService implements QueuableService
{
    use DispatchableService;

    private Account $account;
    private array $data;
    private int $status;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'licence_key' => 'required|string:4096',
        ];
    }

    /**
     * Check if the licence key given by the user is a valid licence key.
     * If it is, activate the licence key and set the valid_until_at date.
     *
     * @param  array  $data
     * @return void
     */
    public function handle(array $data): void
    {
        $this->validate($data);
        $this->data = $data;
        $this->account = Account::findOrFail($data['account_id']);

        $this->validateEnvVariables();
        $this->makeRequestToCustomerPortal();
        $this->checkResponseCode();
        $this->decodeAndStoreKey();
    }

    private function validateEnvVariables(): void
    {
        if (config('monica.licence_private_key') === null) {
            throw new NoLicenceKeyEncryptionSetException;
        }
    }

    private function makeRequestToCustomerPortal(): void
    {
        $this->status = app(CustomerPortalCall::class)->execute([
            'licence_key' => $this->data['licence_key'],
        ]);
    }

    private function checkResponseCode(): void
    {
        if ($this->status === 404) {
            throw new Exception(trans('settings.subscriptions_licence_key_does_not_exist'));
        }

        if ($this->status === 410) {
            throw new Exception(trans('settings.subscriptions_licence_key_invalid'));
        }

        if ($this->status !== 200) {
            throw new Exception(trans('settings.subscriptions_licence_key_problem'));
        }
    }

    private function decodeAndStoreKey(): void
    {
        $encrypter = app('license.encrypter');
        $licenceKey = $encrypter->decrypt($this->data['licence_key']);

        $this->account->licence_key = $this->data['licence_key'];
        $this->account->valid_until_at = $licenceKey['next_check_at'];
        $this->account->purchaser_email = $licenceKey['purchaser_email'];
        $this->account->frequency = $licenceKey['frequency'];
        $this->account->save();
    }
}
