<?php

namespace App\Services\DavClient;

use Illuminate\Support\Arr;
use App\Services\BaseService;
use App\Models\Account\AddressBook;
use Illuminate\Support\Facades\Log;
use App\Services\DavClient\Dav\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Models\Account\AddressBookSubscription;

class AddAddressBook extends BaseService
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
            'base_uri' => 'required|string|url',
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Add a new Adress Book.
     *
     * @param array $data
     * @param GuzzleClient $httpClient
     * @return AddressBookSubscription
     */
    public function execute(array $data, GuzzleClient $httpClient = null): AddressBookSubscription
    {
        $this->validate($data);

        $addressbookData = $this->getAddressBookData($data, $httpClient);

        $lastAddressBook = AddressBook::where('account_id', $data['account_id'])
            ->get()
            ->last();

        $lastId = 0;
        if ($lastAddressBook) {
            $lastId = intval(preg_replace('/\w+(\d+)/i', '$1', $lastAddressBook->addressBookId));
        }
        $nextAddressBookId = 'contacts'.($lastId + 1);

        $addressbook = AddressBook::create([
            'account_id' => $data['account_id'],
            'user_id' => $data['user_id'],
            'addressBookId' => $nextAddressBookId,
            'name' => $addressbookData['name'],
        ]);
        $subscription = AddressBookSubscription::create([
            'account_id' => $data['account_id'],
            'user_id' => $data['user_id'],
            'username' => $data['username'],
            'addressbook_id' => $addressbook->id,
            'uri' => $addressbookData['uri'],
            'capabilities' => $addressbookData['capabilities'],
        ]);
        $subscription->password = $data['password'];
        $subscription->save();

        return $subscription;
    }

    public function getAddressBookData(array $data, GuzzleClient $httpClient = null): ?array
    {
        try {
            $client = $this->getClient($data, $httpClient);

            $uri = $this->getAddressBookBaseUri($data, $client);

            $client->setBaseUri($uri);

            $capabilities = $this->getCapabilities($client);

            $name = $client->getProperty('{DAV:}displayname');

            return [
                'uri' => $uri,
                'capabilities' => $capabilities,
                'name' => $name,
            ];
        } catch (ClientException $e) {
            Log::error(__CLASS__.' getAddressBookBaseUri: '.$e->getMessage(), $e);
        }

        return null;
    }

    private function getAddressBookBaseUri(array $data, Client $client): string
    {
        $baseUri = $client->getServiceUrl();
        $client->setBaseUri($baseUri);

        // Check the OPTIONS of the server
        $this->checkOptions($client);

        // Get the principal of this account
        $principal = $this->getCurrentUserPrincipal($client);

        // Get the AddressBook of this principal
        $addressbook = $this->getAddressBookUrl($client, $principal);

        return $client->getBaseUri($addressbook);
    }

    /**
     * @see https://tools.ietf.org/html/rfc2518#section-15
     */
    private function checkOptions(Client $client)
    {
        $options = $client->options();
        $options = explode(', ', $options[0]);

        if (! in_array('1', $options) || ! in_array('3', $options) || ! in_array('addressbook', $options)) {
            throw new \Exception('server is not compliant with rfc2518 section 15.1, or rfc6352 section 6.1');
        }
    }

    /**
     * @see https://tools.ietf.org/html/rfc5397#section-3
     */
    private function getCurrentUserPrincipal(Client $client): string
    {
        $prop = $client->getProperty('{DAV:}current-user-principal');

        if (is_null($prop) || count($prop) == 0) {
            throw new \Exception('Server does not support rfc 5397 section 3 (DAV:current-user-principal)');
        }

        return $prop[0]['value'];
    }

    /**
     * @see https://tools.ietf.org/html/rfc6352#section-7.1.1
     */
    private function getAddressBookHome(Client $client, string $principal): string
    {
        $prop = $client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-home-set', $principal);

        if (is_null($prop) || count($prop) == 0) {
            throw new \Exception('Server does not support rfc 6352 section 7.1.1 (CARD:addressbook-home-set)');
        }

        return $prop[0]['value'];
    }

    private function getAddressBookUrl(Client $client, string $principal): ?string
    {
        $home = $this->getAddressBookHome($client, $principal);

        $books = $client->propfind($home, [], 1);

        foreach ($books as $book => $properties) {
            if ($book == $home) {
                continue;
            }

            if ($resources = Arr::get($properties, '{DAV:}resourcetype', null)) {
                if ($resources->is('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook')) {
                    return $book;
                }
            }
        }
    }

    private function getCapabilities(Client $client)
    {
        return $this->getSupportedReportSet($client)
            +
            $this->getSupportedAddressData($client);
    }

    private function getSupportedReportSet(Client $client)
    {
        $supportedReportSet = $client->getSupportedReportSet();

        $addressbookMultiget = in_array('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-multiget', $supportedReportSet);
        $syncCollection = in_array('{DAV:}sync-collection', $supportedReportSet);

        return [
            'addressbookMultiget' => $addressbookMultiget,
            'syncCollection' => $syncCollection,
        ];
    }

    private function getSupportedAddressData(Client $client)
    {
        // get the supported card format
        $addressData = collect($client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}supported-address-data'));
        $datas = $addressData->firstWhere('attributes.version', '4.0');
        if (! $datas) {
            $datas = $addressData->firstWhere('attributes.version', '3.0');
        }

        if (! $datas) {
            // It should not happen !
            $datas = [
                'attributes' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ];
        }

        return [
            'addressData' => [
                'content-type' => $datas['attributes']['content-type'],
                'version' => $datas['attributes']['version'],
            ],
        ];
    }

    private function getClient(array $data, GuzzleClient $client = null): Client
    {
        $settings = Arr::only($data, [
            'base_uri',
            'username',
            'password',
        ]);

        return new Client($settings, $client);
    }
}
