<?php

namespace App\Services\DavClient;

use App\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Request;
use App\Services\BaseService;
use GuzzleHttp\Promise\Promise;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;
use App\Models\Account\AddressBook;
use Illuminate\Support\Facades\Log;
use App\Services\DavClient\Dav\Client;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Models\Account\AddressBookSubscription;
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
        ];
    }

    /**
     * @param array $data
     * @return VCard
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
            $this->sync($subscription, $backend, $httpClient);
        } catch (ClientException $e) {
            Log::error(__CLASS__.' execute: '.$e->getMessage(), $e);
        }
    }

    private function sync($subscription, $backend, $httpClient)
    {
        $client = $this->getClient($subscription, $httpClient);

        (new AddressBookSynchronizer($subscription, $client, $backend))->sync();
    }

    private function getClient(AddressBookSubscription $subscription, GuzzleClient $client = null): Client
    {
        return new Client([
            'base_uri' => $subscription->uri,
            'username' => $subscription->username,
            'password' => $subscription->password,
        ], $client);
    }
}
