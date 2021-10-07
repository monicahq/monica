<?php

namespace App\Services\DavClient;

use App\Models\User\User;
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
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

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
            'user_id' => 'required|integer|exists:users,id',
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

        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        $subscription = AddressBookSubscription::where('account_id', $data['account_id'])
            ->findOrFail($data['addressbook_subscription_id']);

        $backend = new CardDAVBackend($user);

        try {
            $this->sync($data, $subscription, $backend);
        } catch (ClientException $e) {
            Log::error(__CLASS__.' execute: '.$e->getMessage(), [$e]);
            if ($e->hasResponse()) {
                Log::error(__CLASS__.' execute: '.$e->getResponse()->getBody());
            }
        }
    }

    private function sync(array $data, AddressBookSubscription $subscription, CardDAVBackend $backend)
    {
        $client = $this->getDavClient($subscription);
        $sync = new SyncDto($subscription, $client, $backend);
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
