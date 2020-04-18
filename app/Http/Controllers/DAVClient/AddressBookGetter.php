<?php

namespace App\Http\Controllers\DAVClient;

use Illuminate\Support\Arr;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Http\Controllers\DAVClient\Dav\Client;
use App\Http\Controllers\DAVClient\Dav\DavClientException;

class AddressBookGetter extends BaseService
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get address book data: uri, capabilities, and name.
     *
     * @return array|null
     */
    public function getAddressBookData(): ?array
    {
        try {
            $uri = $this->getAddressBookBaseUri();

            $this->client->setBaseUri($uri);

            $capabilities = $this->getCapabilities();

            $name = $this->client->getProperty('{DAV:}displayname');

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

    /**
     * Calculate address book base uri.
     *
     * @return string
     */
    private function getAddressBookBaseUri(): string
    {
        $baseUri = $this->client->getServiceUrl();
        $this->client->setBaseUri($baseUri);

        // Check the OPTIONS of the server
        $this->checkOptions();

        // Get the principal of this account
        $principal = $this->getCurrentUserPrincipal();

        // Get the AddressBook of this principal
        $addressbook = $this->getAddressBookUrl($principal);

        return $this->client->getBaseUri($addressbook);
    }

    /**
     * Check options of the server.
     *
     * @see https://tools.ietf.org/html/rfc2518#section-15
     * @throws DavClientException
     */
    private function checkOptions()
    {
        $options = $this->client->options();
        $options = explode(', ', $options[0]);

        if (! in_array('1', $options) || ! in_array('3', $options) || ! in_array('addressbook', $options)) {
            throw new DavClientException('server is not compliant with rfc2518 section 15.1, or rfc6352 section 6.1');
        }
    }

    /**
     * Get principal name.
     *
     * @return string
     * @see https://tools.ietf.org/html/rfc5397#section-3
     */
    private function getCurrentUserPrincipal(): string
    {
        $prop = $this->client->getProperty('{DAV:}current-user-principal');

        if (is_null($prop) || count($prop) == 0) {
            throw new \Exception('Server does not support rfc 5397 section 3 (DAV:current-user-principal)');
        }

        return $prop[0]['value'];
    }

    /**
     * Get addressbook url.
     *
     * @return string
     * @see https://tools.ietf.org/html/rfc6352#section-7.1.1
     */
    private function getAddressBookHome(string $principal): string
    {
        $prop = $this->client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-home-set', $principal);

        if (is_null($prop) || count($prop) == 0) {
            throw new \Exception('Server does not support rfc 6352 section 7.1.1 (CARD:addressbook-home-set)');
        }

        return $prop[0]['value'];
    }

    /**
     * Get Url fro address book.
     *
     * @return string|null
     */
    private function getAddressBookUrl(string $principal): ?string
    {
        $home = $this->getAddressBookHome($principal);

        $books = $this->client->propfind($home, [], 1);

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

    /**
     * Get capabilities properties.
     *
     * @return array
     */
    private function getCapabilities()
    {
        return $this->getSupportedReportSet()
            +
            $this->getSupportedAddressData();
    }

    /**
     * Get supported-report-set property.
     *
     * @return array
     */
    private function getSupportedReportSet(): array
    {
        $supportedReportSet = $this->client->getSupportedReportSet();

        $addressbookMultiget = in_array('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-multiget', $supportedReportSet);
        $addressbookQuery = in_array('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-query', $supportedReportSet);
        $syncCollection = in_array('{DAV:}sync-collection', $supportedReportSet);

        return [
            'addressbookMultiget' => $addressbookMultiget,
            'addressbookQuery' => $addressbookQuery,
            'syncCollection' => $syncCollection,
        ];
    }

    /**
     * Get supported-address-data property.
     *
     * @return array
     */
    private function getSupportedAddressData(): array
    {
        // get the supported card format
        $addressData = collect($this->client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}supported-address-data'));
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
}
