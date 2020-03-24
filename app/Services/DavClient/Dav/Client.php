<?php

namespace App\Services\DavClient\Dav;

use Sabre\DAV\Xml\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;
use Sabre\DAV\Xml\Request\PropPatch;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;

class Client
{
    /**
     * The xml service.
     *
     * @var Service
     */
    public $xml;

    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * Create a new client.
     *
     * @param array $settings
     * @param GuzzleClient $client
     */
    public function __construct(array $settings, GuzzleClient $client = null)
    {
        if (is_null($client) && !isset($settings['base_uri'])) {
            throw new \InvalidArgumentException('A baseUri must be provided');
        }

        $this->client = is_null($client) ? new GuzzleClient([
            'base_uri' => $settings['base_uri'],
            'auth' => [
                $settings['username'],
                $settings['password'],
            ],
        ]) : $client;

        $this->xml = new Service();
    }

    /**
     * Follow rfc6764 to get carddav service url
     *
     * @see https://tools.ietf.org/html/rfc6764
     */
    public function getServiceUrl()
    {
        // Get well-known register (section 9.1)
        $wkUri = $this->getBaseUri('/.well-known/carddav');

        $response = $this->client->get($wkUri, ['allow_redirects' => false]);

        $code = $response->getStatusCode();
        if (($code === 301 || $code === 302) && $response->hasHeader('Location')) {
            return $response->getHeader('Location')[0];
        }

        // Get service name register (section 9.2)
        $target = $this->getServiceUrlSrv('_carddavs._tcp', true);
        if (is_null($target)) {
            $target = $this->getServiceUrlSrv('_carddav._tcp', false);
        }

        return $target;
    }

    /**
     * Service Discovery via SRV Records
     *
     * @see https://tools.ietf.org/html/rfc6352#section-11
     */
    private function getServiceUrlSrv(string $name, bool $https): ?string
    {
        $host = parse_url($this->getBaseUri(), PHP_URL_HOST);
        $entry = dns_get_record($name.'.'.$host, DNS_SRV);

        if ($entry && count($entry) > 0) {
            $target = isset($entry[0]['target']) ? $entry[0]['target'] : null;
            $port = isset($entry[0]['port']) ? $entry[0]['port'] : null;
            if ($target) {
                if ($port === 443 && $https) {
                    $port = null;
                } else if ($port === 80 && !$https) {
                    $port = null;
                }
                return ($https ? 'https' : 'http') . '://' . $target . (is_null($port) ? '' : ':'.$port);
            }
        }
    }

    public function getBaseUri(?string $path = null)
    {
        $baseUri = $this->client->getConfig('base_uri');
        return is_null($path) ? $baseUri : $baseUri->withPath($path);
    }

    public function setBaseUri($uri)
    {
        $this->client = new GuzzleClient(
            Arr::except($this->client->getConfig(), ['base_uri'])
            +
            ['base_uri' => $uri]
        );
    }


    /**
     * Does a PROPFIND request.
     *
     * The list of requested properties must be specified as an array, in clark
     * notation.
     *
     * The returned array will contain a list of filenames as keys, and
     * properties as values.
     *
     * The properties array will contain the list of properties. Only properties
     * that are actually returned from the server (without error) will be
     * returned, anything else is discarded.
     *
     * Depth should be either 0 or 1. A depth of 1 will cause a request to be
     * made to the server to also return all child resources.
     *
     * @param string $url
     * @param array  $properties
     * @param int    $depth
     *
     * @return array
     */
    public function propFind(string $url, array $properties, int $depth = 0) : array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElementNS('DAV:', 'd:propfind'));
        $prop = $root->appendChild($dom->createElement('d:prop'));

        $namespaces = [
            'DAV:' => 'd'
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        $request = new Request('PROPFIND', $url, [
            'Depth' => $depth,
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body);

        $response = $this->client->send($request);

        $result = $this->parseMultiStatus((string) $response->getBody());

        // If depth was 0, we only return the top item
        if ($depth === 0) {
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

    /**
     * @see https://tools.ietf.org/html/rfc6578
     */
    public function syncCollection(string $url, string $syncToken, array $properties) : array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElementNS('DAV:', 'd:sync-collection'));

        $root->appendChild($dom->createElement('d:sync-token', $syncToken));
        $root->appendChild($dom->createElement('d:sync-level', '1'));

        $prop = $root->appendChild($dom->createElement('d:prop'));

        $namespaces = [
            'DAV:' => 'd'
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        $request = new Request('REPORT', $url, [
            'Depth' => '0',
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body);

        $response = $this->client->send($request);

        $result = $this->parseMultiStatus((string) $response->getBody());

        return $result;
    }

    /**
     * @see https://tools.ietf.org/html/rfc6352#section-8.7
     */
    public function addressbookMultiget(string $url, array $properties, Collection $contacts) : array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElementNS(CardDAVPlugin::NS_CARDDAV, 'card:addressbook-multiget'));
        $dom->createAttributeNS('DAV:', 'd:e');

        $prop = $root->appendChild($dom->createElement('d:prop'));

        $namespaces = [
            'DAV:' => 'd',
            CardDAVPlugin::NS_CARDDAV => 'card',
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        foreach ($contacts as $contact) {
            $root->appendChild($dom->createElement('d:href', $contact));
        }

        $body = $dom->saveXML();

        $request = new Request('REPORT', $url, [
            'Depth' => '1',
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body);

        $response = $this->client->send($request);

        $result = $this->parseMultiStatus((string) $response->getBody());

        return $result;
    }

    private function fetchProperties($dom, $prop, array $properties, array $namespaces)
    {
        foreach ($properties as $property) {
            list($namespace, $elementName) = Service::parseClarkNotation($property);

            $value = Arr::get($namespaces, $namespace, null);
            if (!is_null($value)) {
                $element = $dom->createElement("$value:$elementName");
            } else {
                $element = $dom->createElementNS($namespace, 'x:'.$elementName);
            }

            $prop->appendChild($element);
        }
    }

    /**
     * @return string|null|mixed
     */
    public function getProperty(string $property, string $url = '')
    {
        $propfind = $this->propfind($url, [
            $property,
        ]);

        if (!isset($propfind[$property])) {
            return null;
        }

        $prop = $propfind[$property];

        if (is_string($prop)) {
            return $prop;
        } else if (is_array($prop)) {
            $value = $prop[0];
            if (is_string($value)) {
                return $value;
            } else if (is_array($value)) {
                return Arr::get($value, 'value', $value);
            }
        }

        return $prop;
    }

    public function getSupportedReportSet()
    {
        $propName = '{DAV:}supported-report-set';
        $supportedReportSets = $this->propFind('', [$propName])[$propName];

        return array_map(function ($supportedReportSet) {
            foreach ($supportedReportSet['value'] as $kind) {
                if ($kind['name'] == '{DAV:}report') {
                    foreach($kind['value'] as $type) {
                        return $type['name'];
                    }
                }
            }
        }, $supportedReportSets);
    }

    /**
     * Updates a list of properties on the server.
     *
     * The list of properties must have clark-notation properties for the keys,
     * and the actual (string) value for the value. If the value is null, an
     * attempt is made to delete the property.
     *
     * @param string $url
     * @param array  $properties
     *
     * @return bool
     */
    public function propPatch(string $url, array $properties) : bool
    {
        $propPatch = new PropPatch();
        $propPatch->properties = $properties;
        $xml = $this->xml->write(
            '{DAV:}propertyupdate',
            $propPatch
        );

        $request = new Request('PROPPATCH', $url, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $xml);
        $response = $this->client->send($request);

        if ($response->getStatusCode() === 207) {
            // If it's a 207, the request could still have failed, but the
            // information is hidden in the response body.
            $result = $this->parseMultiStatus((string) $response->getBody());

            $errorProperties = [];
            foreach ($result as $statusList) {
                foreach ($statusList as $status => $properties) {
                    if ($status >= 400) {
                        foreach ($properties as $propName => $propValue) {
                            $errorProperties[] = $propName.' ('.$status.')';
                        }
                    }
                }
            }
            if ($errorProperties) {
                throw new ClientException('PROPPATCH failed. The following properties errored: '.implode(', ', $errorProperties), $request);
            }
        }

        return true;
    }

    /**
     * Performs an HTTP options request.
     *
     * This method returns all the features from the 'DAV:' header as an array.
     * If there was no DAV header, or no contents this method will return an
     * empty array.
     *
     * @return array
     */
    public function options() : array
    {
        $request = new Request('OPTIONS', '');
        $response = $this->client->send($request);

        $dav = $response->getHeader('Dav');
        if (!$dav) {
            return [];
        }

        foreach ($dav as &$v) {
            $v = trim($v);
        }

        return $dav;
    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * If the specified url is relative, it will be expanded based on the base
     * url.
     *
     * The returned array contains 3 keys:
     *   * body - the response body
     *   * httpCode - a HTTP code (200, 404, etc)
     *   * headers - a list of response http headers. The header names have
     *     been lowercased.
     *
     * For large uploads, it's highly recommended to specify body as a stream
     * resource. You can easily do this by simply passing the result of
     * fopen(..., 'r').
     *
     * This method will throw an exception if an HTTP error was received. Any
     * HTTP status code above 399 is considered an error.
     *
     * Note that it is no longer recommended to use this method, use the send()
     * method instead.
     *
     * @param string               $method
     * @param string               $url
     * @param string|resource|null $body
     * @param array                $headers
     *
     * @throws ClientException, in case a curl error occurred
     *
     * @return array
     */
    public function request(string $method, string $url = '', ?string $body = null, array $headers = []) : array
    {
        $response = $this->client->send(new Request($method, $url, $headers, $body));

        return [
            'body' => (string) $response->getBody(),
            'statusCode' => $response->getStatusCode(),
            'headers' => array_change_key_case($response->getHeaders()),
        ];
    }

    /**
     * Parses a WebDAV multistatus response body.
     *
     * This method returns an array with the following structure
     *
     * [
     *   'url/to/resource' => [
     *     '200' => [
     *        '{DAV:}property1' => 'value1',
     *        '{DAV:}property2' => 'value2',
     *     ],
     *     '404' => [
     *        '{DAV:}property1' => null,
     *        '{DAV:}property2' => null,
     *     ],
     *   ],
     *   'url/to/resource2' => [
     *      .. etc ..
     *   ]
     * ]
     *
     *
     * @param string $body xml body
     *
     * @return array
     */
    public function parseMultiStatus(string $body) : array
    {
        $multistatus = $this->xml->expect('{DAV:}multistatus', $body);

        $result = [];

        foreach ($multistatus->getResponses() as $response) {
            $result[$response->getHref()] = $response->getResponseProperties();
        }

        $synctoken = $multistatus->getSyncToken();
        if (! empty($synctoken)) {
            $result['synctoken'] = $synctoken;
        }

        return $result;
    }
}
