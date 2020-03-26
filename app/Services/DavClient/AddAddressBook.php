<?php

namespace App\Services\DavClient;

use App\Models\User\User;
use Illuminate\Support\Arr;
use App\Services\BaseService;
use Sabre\VObject\Component\VCard;
use App\Models\Account\AddressBook;
use App\Services\DavClient\Dav\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;

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
            'base_uri' => 'required|string|url',
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     *
     * @param array $data
     * @return VCard
     */
    public function execute(array $data, GuzzleClient $httpClient = null)
    {
        $data = [
            'account_id' => 2,
            'base_uri' => 'http://monica.test/dav/',
            'username' => 'admin@admin.com',
            'password' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZjUzYmRkYzJhNzQ0ZTgxZDRiYmUwMTQ2ODY1YTA3ZTIyMWM0ZTQ5YzkyNzJkN2FhZmE1ODk5ZDRmM2NkZGRlMWQyMWQ5MmM5M2E4YTNkMGMiLCJpYXQiOjE1ODQ4MDkxNDQsIm5iZiI6MTU4NDgwOTE0NCwiZXhwIjoxNjE2MzQ1MTQ0LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.MUtcgy3PWk9McA59zx4SBJxKAkdSiGv1a9ZtVKwlgtk09bJEx1lJgymGSfDlrNqKvD2LqhTu0y95jLZNoj4-uM6DBZm3RMo18mw2xCEywB4st1hZpMYSYoOmtrOcsZweoP5r31zf_jzMX3mLde6MAeEkJcotGfO9z57M74FquLKixZRLvVruES2DcZoL1hwCKoxvv11BGRE78RQsWiipv0cfgmcSNEQVR820BWkM0X_4WwpufJdzZ5p1EpTy5AP2XXlx6amGXqxgMUIY7C-KyF1uw1Rmr6B-bTcMLJHZBH6TzU0yFoaJnhZZ9tJFyf7E70BL8SaO9_P6nA7ACjDREjAJBD9dZYrP46G-mqJXjWyVOcDVJZNW7dhF5vnEp7gghIVWhAm4lLy5nPI_CNpB0mqPrdkj57Avoi3MAEwf4ADy9CZp1EoLZIvNjBuMpgwwONTF5oP18NMaHJcsbFkmviY7eW-DIuIcNtCuoAM7Q4ulhuVX4tVry5NLsiab0_W8_l63C_n1-ICpv2t04jSh9H3SwgIXAZXhe-0vMt0gTIc3c_1HZ4eRd1kOuUs-708Esiq7J_Nt98PJZB8AP6qbeuScI0Cxnm7IulJ1WaI7mLjA7JPDvISeL2rrYjwqmguDbA8nQ7UjEq1dLN-PaAaL08p_iKU3ssPV2YziNSi_Alc',
        ];

        /* TESTS */

        $this->validate($data);

        $addressbook = $this->getAddressBook($data, $httpClient);

        $lastAddressBook = AddressBook::where('account_id', $data['account_id'])
            ->get()
            ->last();

        $lastId = 0;
        if ($lastAddressBook) {
            $lastId = intval(preg_replace('/\w+(\d+)/i', '$1', $lastAddressBook->identifier));
        }
        $nextAddressBookId = 'contacts'.($lastId+1);

        $addressbook = AddressBook::create([
            'account_id' => $data['account_id'],
            'user_id' => $data['user_id'],
            'addressBookId' => $nextAddressBookId,
            'username' => $data['username'],
        ]
            + $addressbook
        );
        $addressbook->password = $data['password'];
        $addressbook->save();

        return $addressbook;
    }

    public function getAddressBook(array $data, GuzzleClient $httpClient = null)
    {
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
    }


    private function getAddressBookBaseUri(array $data, Client $client) : string
    {
        try {

            $baseUri = $client->getServiceUrl();
            $client->setBaseUri($baseUri);

            $this->checkOptions($client);


            // /dav/principals/admin@admin.com/
            $principal = $this->getCurrentUserPrincipal($client);

            $addressbook = $this->getAddressBookUrl($client, $principal);

            return $client->getBaseUri($addressbook);

        } catch (ClientException $e) {
            $r = $e->getResponse();
            $s = (string) $r->getBody();
        } catch (\Exception $e) {
        }
    }

    /**
     * @see https://tools.ietf.org/html/rfc2518#section-15
     */
    private function checkOptions(Client $client)
    {
        $options = $client->options();
        $options = explode(', ', $options[0]);

        if (!in_array('1', $options) || !in_array('3', $options) || !in_array('addressbook', $options)) {
            throw new \Exception('server is not compliant with rfc2518 section 15.1, or rfc6352 section 6.1');
        }
    }

    /**
     * @see https://tools.ietf.org/html/rfc5397#section-3
     */
    private function getCurrentUserPrincipal(Client $client) : string
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
    private function getAddressBookHome(Client $client, string $principal) : string
    {
        $prop = $client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-home-set', $principal);

        if (is_null($prop) || count($prop) == 0) {
            throw new \Exception('Server does not support rfc 6352 section 7.1.1 (CARD:addressbook-home-set)');
        }

        return $prop[0]['value'];
    }

    private function getAddressBookUrl(Client $client, string $principal) : ?string
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
        if (!$datas) {
            $datas = $addressData->firstWhere('attributes.version', '3.0');
        }

        if (!$datas) {
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

    private function getClient(array $data, GuzzleClient $client = null) : Client
    {
        $settings = Arr::only($data, [
            'base_uri',
            'username',
            'password',
        ]);

        return new Client($settings, $client);
    }
}
