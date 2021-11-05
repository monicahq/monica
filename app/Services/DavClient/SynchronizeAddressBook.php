<?php

namespace App\Services\DavClient;

use Illuminate\Support\Arr;
use App\Services\BaseService;
use App\Helpers\AccountHelper;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\AddressBookSynchronizer;

class SynchronizeAddressBook extends BaseService
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
            'addressbook_subscription_id' => 'required|integer|exists:addressbook_subscriptions,id',
            'force' => 'nullable|boolean',
        ];
    }

    /**
     * @param  array  $data
     * @return void
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $account = Account::find($data['account_id']);
        if (AccountHelper::hasReachedContactLimit($account)
            && AccountHelper::hasLimitations($account)
            && ! $account->legacy_free_plan_unlimited_contacts) {
            abort(402);
        }

        $subscription = AddressBookSubscription::where('account_id', $data['account_id'])
            ->findOrFail($data['addressbook_subscription_id']);

        try {
            $this->sync($data, $subscription);
        } catch (ClientException $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'body' => $e->hasResponse() ? $e->getResponse()->getBody() : null,
                $e,
            ]);
        }
    }

    private function sync(array $data, AddressBookSubscription $subscription)
    {
        $client = $this->getDavClient($subscription);
        $sync = new SyncDto($subscription, $client);
        $force = Arr::get($data, 'force', false);

        app(AddressBookSynchronizer::class)
            ->execute($sync, $force);
    }

    private function getDavClient(AddressBookSubscription $subscription): DavClient
    {
        return app(DavClient::class)
            ->setBaseUri($subscription->uri)
            ->setCredentials($subscription->username, $subscription->password);
    }
}
