<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClientException;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavServerNotCompliantException;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasClient;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sabre\CardDAV\Plugin as CardDav;

class AddressBookGetter
{
    use HasClient;

    /**
     * Get address book data: uri, capabilities, and name.
     */
    public function execute(): ?array
    {
        try {
            return $this->getAddressBookData();
        } catch (ClientException $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [$e]);
            throw $e;
        }
    }

    /**
     * Get address book data: uri, capabilities, and name.
     */
    private function getAddressBookData(): array
    {
        $uri = $this->getAddressBookBaseUri();

        $this->client->setBaseUri($uri);

        if (Str::startsWith($uri, 'https://www.googleapis.com')) {
            // Google API sucks
            $capabilities = [
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '3.0',
                ],
            ];
        } else {
            $capabilities = $this->getCapabilities();
        }

        $name = $this->client->getProperty('{DAV:}displayname');

        return [
            'uri' => $uri,
            'capabilities' => $capabilities,
            'name' => $name,
        ];
    }

    /**
     * Calculate address book base uri.
     */
    private function getAddressBookBaseUri(): string
    {
        try {
            // Get the principal of this account
            $principal = $this->getCurrentUserPrincipal();
            $baseUri = $this->client->path($principal);
        } catch (\Exception $e) {
            $baseUri = $this->client->getServiceUrl();
        }

        if ($baseUri) {
            $this->client->setBaseUri($baseUri);
        }

        if (! Str::contains($baseUri, 'https://www.googleapis.com')) {
            // Google API does not follow rfc2518 section-15 !

            // Check the OPTIONS of the server
            $this->checkOptions();
        }

        // Get the principal of this account
        $principal = $this->getCurrentUserPrincipal();

        // Get the AddressBook of this principal
        $addressBook = $this->getAddressBookUrl($principal);
        $addressBookUrl = $this->client->path($addressBook);

        if (! Str::contains($addressBookUrl, 'https://www.googleapis.com')) {
            // Check the OPTIONS of the server
            $this->checkOptions(true, $addressBookUrl);
        }

        if ($addressBook === null) {
            throw new DavClientException('No address book found');
        }

        return $addressBookUrl;
    }

    /**
     * Check options of the server.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc2518#section-15
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-6.1
     *
     * @throws DavServerNotCompliantException
     */
    private function checkOptions(bool $addressbook = false, string $url = ''): void
    {
        $options = $this->client->options($url);

        if (! in_array('1', $options) || ! in_array('3', $options) || ($addressbook && ! in_array('addressbook', $options))) {
            throw new DavServerNotCompliantException('server is not compliant with rfc2518 section 15.1, or rfc6352 section 6.1');
        }
    }

    /**
     * Get principal name.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc5397#section-3
     *
     * @throws DavServerNotCompliantException
     */
    private function getCurrentUserPrincipal(): string
    {
        $prop = $this->client->getProperty('{DAV:}current-user-principal');

        if (is_null($prop) || empty($prop)) {
            throw new DavServerNotCompliantException('Server does not support rfc 5397 section 3 (DAV:current-user-principal)');
        } elseif (is_string($prop)) {
            return $prop;
        }

        return $prop[0]['value'];
    }

    /**
     * Get addressbook url.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-7.1.1
     *
     * @throws DavServerNotCompliantException
     */
    private function getAddressBookHome(string $principal): string
    {
        $prop = $this->client->getProperty('{'.CardDav::NS_CARDDAV.'}addressbook-home-set', $principal);

        if (is_null($prop) || empty($prop)) {
            throw new DavServerNotCompliantException('Server does not support rfc 6352 section 7.1.1 (CARD:addressbook-home-set)');
        } elseif (is_string($prop)) {
            return $prop;
        }

        return $prop[0]['value'];
    }

    /**
     * Get Url fro address book.
     */
    private function getAddressBookUrl(string $principal): ?string
    {
        $home = $this->getAddressBookHome($principal);

        $books = $this->client->propfind('{DAV:}resourcetype', 1, [], $home);

        foreach ($books as $book => $properties) {
            if ($book == $home) {
                continue;
            }

            if (($resources = Arr::get($properties, '{DAV:}resourcetype', null)) &&
                $resources->is('{'.CardDav::NS_CARDDAV.'}addressbook')) {
                return $book;
            }
        }

        return null;
    }

    /**
     * Get capabilities properties.
     */
    private function getCapabilities(): array
    {
        return $this->getSupportedReportSet()
            +
            $this->getSupportedAddressData();
    }

    /**
     * Get supported-report-set property.
     */
    private function getSupportedReportSet(): array
    {
        $supportedReportSet = $this->client->getSupportedReportSet();

        $addressbookMultiget = in_array('{'.CardDav::NS_CARDDAV.'}addressbook-multiget', $supportedReportSet);
        $addressbookQuery = in_array('{'.CardDav::NS_CARDDAV.'}addressbook-query', $supportedReportSet);
        $syncCollection = in_array('{DAV:}sync-collection', $supportedReportSet);

        return [
            'addressbookMultiget' => $addressbookMultiget,
            'addressbookQuery' => $addressbookQuery,
            'syncCollection' => $syncCollection,
        ];
    }

    /**
     * Get supported-address-data property.
     */
    private function getSupportedAddressData(): array
    {
        // get the supported card format
        $addressData = collect($this->client->getProperty('{'.CardDav::NS_CARDDAV.'}supported-address-data'));
        $data = $addressData->firstWhere('attributes.version', '4.0');
        if (! $data) {
            $data = $addressData->firstWhere('attributes.version', '3.0');
        }

        if (! $data) {
            // It should not happen !
            $data = [
                'attributes' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ];
        }

        return [
            'addressData' => [
                'content-type' => Arr::get($data, 'attributes.content-type'),
                'version' => Arr::get($data, 'attributes.version'),
            ],
        ];
    }
}
