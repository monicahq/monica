<?php

namespace App\Services\DavClient;

use App\Models\User\User;
use Illuminate\Support\Arr;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use App\Http\Controllers\DAVClient\Dav\Client;
use App\Models\Account\AddressBookSubscription;
use App\Http\Controllers\DAVClient\AddressBookSynchronizer;
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
     * @param array $data
     * @return void
     */
    public function execute(array $data, GuzzleClient $httpClient = null)
    {
        $this->validate($data);

        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        $subscription = AddressBookSubscription::where('account_id', $data['account_id'])
            ->findOrFail($data['addressbook_subscription_id']);

        $backend = new CardDAVBackend($user);

        try {
            $this->sync($data, $subscription, $backend, $httpClient);
        } catch (ClientException $e) {
            Log::error(__CLASS__.' execute: '.$e->getMessage(), [$e]);
            if ($e->hasResponse()) {
                Log::error(__CLASS__.' execute: '.$e->getResponse()->getBody());
            }
        }
    }

    private function sync(array $data, AddressBookSubscription $subscription, CardDAVBackend $backend, ?GuzzleClient $httpClient)
    {
        $client = $this->getClient($subscription, $httpClient);

        $synchronizer = new AddressBookSynchronizer($subscription, $client, $backend);

        if (! Arr::has($data, 'force') || ! $data['force']) {
            $synchronizer->sync();
        } else {
            $synchronizer->forcesync();
        }
    }

    private function getClient(AddressBookSubscription $subscription, ?GuzzleClient $client): Client
    {
        return new Client([
            'base_uri' => $subscription->uri,
            'username' => $subscription->username,
            'password' => $subscription->password,
        ], $client);
    }
}
