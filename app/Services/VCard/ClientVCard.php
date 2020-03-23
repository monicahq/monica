<?php

namespace App\Services\VCard;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\BaseService;
use Sabre\VObject\Component\VCard;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;

class ClientVCard extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     *
     * @param array $data
     * @return VCard
     */
    public function execute(array $data, GuzzleClient $httpClient = null)
    {
        $addressbook = $this->getAddressBook($data, $httpClient);

        try {

            $client = $this->getClient($httpClient);

            $client->setBaseUri($addressbook);

            $displayname = $client->getProperty('{DAV:}displayname');


            $supportedReportSet = $client->getSupportedReportSet();

            // INITIAL SYNC
            if (in_array('{DAV:}sync-collection', $supportedReportSet)) {

                // get ctag

                //$syncToken = $client->getProperty('{DAV:}sync-token');

                // initial sync
                $collection = $client->syncCollection('', '', [
                    '{DAV:}getcontenttype',
                    '{DAV:}getetag'
                ]);

            } else {

                // synchronisation

                $collection = $client->propFind('', [
                    '{DAV:}getcontenttype',
                    '{DAV:}getetag',
                ], 1);
            }


            $refresh = collect();
            foreach ($collection as $href => $contact) {
                if (isset($contact[200]) && Str::contains($contact[200]['{DAV:}getcontenttype'], 'text/vcard')) {
                    // test si le contact existe
                    // si non -> on l'ajoute
                    // si oui : test du etag
                    //    si pas identique : on l'ajoute

                    $refresh->push([
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                    ]);
                }
            }


            if (in_array('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-multiget', $supportedReportSet)) {

                $datas = $client->addressbookMultiget('', [
                    '{DAV:}getetag',
                    '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
                ], $refresh->map(function ($contact) { return $contact['href']; }));

                foreach ($datas as $href => $contact) {
                    if (isset($contact[200])) {
                        $etag = $contact[200]['{DAV:}getetag'];
                        $vcard = $contact[200]['{'.CardDAVPlugin::NS_CARDDAV.'}address-data'];
                    }
                }

            } else {

                foreach ($refresh as $contact) {
                    $c = $client->request('GET', $contact['href']);
                    if ($c['statusCode'] === 200) {
                        $etag = $contact['etag'];
                        $vcard = $c['body'];
                    }
                }
            }

        } catch (ClientException $e) {
            $r = $e->getResponse();
            $s = (string) $r->getBody();
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

    }


    private function getAddressBook(array $data, GuzzleClient $httpClient = null) : string
    {
        $client = $this->getClient($httpClient);

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

    private function checkOptions(Client $client)
    {
        $options = $client->options();
        $options = explode(', ', $options[0]);

        // https://tools.ietf.org/html/rfc2518#section-15
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

        if (is_null($prop)) {
            throw new \Exception('Server does not support rfc 5397 section 3 (DAV:current-user-principal)');
        }

        return $prop;
    }

    /**
     * @see https://tools.ietf.org/html/rfc6352#section-7.1.1
     */
    private function getAddressBookHome(Client $client, string $principal) : string
    {
        $prop = $client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-home-set', $principal);

        if (is_null($prop)) {
            throw new \Exception('Server does not support rfc 6352 section 7.1.1 (CARD:addressbook-home-set)');
        }

        return $prop;
    }

    private function getAddressBookUrl(Client $client, string $principal) : string
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

    private function getClient(GuzzleClient $client = null) : Client
    {
        $settings = [
            'base_uri' => 'http://monica.test/dav/',
            'username' => 'admin@admin.com',
            'password' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZjUzYmRkYzJhNzQ0ZTgxZDRiYmUwMTQ2ODY1YTA3ZTIyMWM0ZTQ5YzkyNzJkN2FhZmE1ODk5ZDRmM2NkZGRlMWQyMWQ5MmM5M2E4YTNkMGMiLCJpYXQiOjE1ODQ4MDkxNDQsIm5iZiI6MTU4NDgwOTE0NCwiZXhwIjoxNjE2MzQ1MTQ0LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.MUtcgy3PWk9McA59zx4SBJxKAkdSiGv1a9ZtVKwlgtk09bJEx1lJgymGSfDlrNqKvD2LqhTu0y95jLZNoj4-uM6DBZm3RMo18mw2xCEywB4st1hZpMYSYoOmtrOcsZweoP5r31zf_jzMX3mLde6MAeEkJcotGfO9z57M74FquLKixZRLvVruES2DcZoL1hwCKoxvv11BGRE78RQsWiipv0cfgmcSNEQVR820BWkM0X_4WwpufJdzZ5p1EpTy5AP2XXlx6amGXqxgMUIY7C-KyF1uw1Rmr6B-bTcMLJHZBH6TzU0yFoaJnhZZ9tJFyf7E70BL8SaO9_P6nA7ACjDREjAJBD9dZYrP46G-mqJXjWyVOcDVJZNW7dhF5vnEp7gghIVWhAm4lLy5nPI_CNpB0mqPrdkj57Avoi3MAEwf4ADy9CZp1EoLZIvNjBuMpgwwONTF5oP18NMaHJcsbFkmviY7eW-DIuIcNtCuoAM7Q4ulhuVX4tVry5NLsiab0_W8_l63C_n1-ICpv2t04jSh9H3SwgIXAZXhe-0vMt0gTIc3c_1HZ4eRd1kOuUs-708Esiq7J_Nt98PJZB8AP6qbeuScI0Cxnm7IulJ1WaI7mLjA7JPDvISeL2rrYjwqmguDbA8nQ7UjEq1dLN-PaAaL08p_iKU3ssPV2YziNSi_Alc',
        ];

        return new Client($settings, $client);
    }

    public function propFind(GuzzleClient $client, $url, array $properties, $depth = 0)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $dom->createElement('d:prop');

        foreach ($properties as $property) {
            list(
                $namespace,
                $elementName
            ) = \Sabre\Xml\Service::parseClarkNotation($property);

            if ('DAV:' === $namespace) {
                $element = $dom->createElement('d:'.$elementName);
            } else {
                $element = $dom->createElementNS($namespace, 'x:'.$elementName);
            }

            $prop->appendChild($element);
        }

        $dom->appendChild($root)->appendChild($prop);
        $body = $dom->saveXML();

        $url = $this->getAbsoluteUrl($url);

        $request = new HTTP\Request('PROPFIND', $url, [
            'Depth' => $depth,
            'Content-Type' => 'application/xml',
        ], $body);

        $response = $this->send($request);

        if ((int) $response->getStatus() >= 400) {
            throw new HTTP\ClientHttpException($response);
        }

        $result = $this->parseMultiStatus($response->getBodyAsString());

        // If depth was 0, we only return the top item
        if (0 === $depth) {
            reset($result);
            $result = current($result);

            return isset($result[200]) ? $result[200] : [];
        }

        $newResult = [];
        foreach ($result as $href => $statusList) {
            $newResult[$href] = isset($statusList[200]) ? $statusList[200] : [];
        }

        return $newResult;
    }

}
