<?php

namespace App\Http\Controllers\DAVClient\Dav;

use GuzzleHttp\Pool;
use Sabre\DAV\Xml\Service;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Sabre\DAV\Xml\Request\PropPatch;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromisorInterface;
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
        if (is_null($client) && ! isset($settings['base_uri'])) {
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
     * Follow rfc6764 to get carddav service url.
     *
     * @see https://tools.ietf.org/html/rfc6764
     */
    public function getServiceUrl()
    {
        // Get well-known register (section 9.1)
        $wkUri = $this->getBaseUri('/.well-known/carddav');

        $response = $this->client->get($wkUri, [
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);

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
     * Service Discovery via SRV Records.
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
                } elseif ($port === 80 && ! $https) {
                    $port = null;
                }

                return ($https ? 'https' : 'http').'://'.$target.(is_null($port) ? '' : ':'.$port);
            }
        }
    }

    /**
     * Get current uri.
     *
     * @param string|null $path
     * @return string
     */
    public function getBaseUri(?string $path = null): string
    {
        $baseUri = $this->client->getConfig('base_uri');

        return is_null($path) ? $baseUri : $baseUri->withPath($path);
    }

    /**
     * Set the base uri of client.
     *
     * @param string $uri
     * @return self
     */
    public function setBaseUri($uri): self
    {
        $this->client = new GuzzleClient(
            Arr::except($this->client->getConfig(), ['base_uri'])
            +
            ['base_uri' => $uri]
        );

        return $this;
    }

    /**
     * Do a PROPFIND request.
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
    public function propFind(string $url, array $properties, int $depth = 0): array
    {
        return $this->propFindAsync($url, $properties, $depth, [
            RequestOptions::SYNCHRONOUS => true,
        ])->wait();
    }

    /**
     * Do a PROPFIND request.
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
     * @return PromiseInterface<array>
     */
    public function propFindAsync(string $url, array $properties, int $depth = 0, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElementNS('DAV:', 'd:propfind'));
        $prop = $root->appendChild($dom->createElement('d:prop'));

        $namespaces = [
            'DAV:' => 'd',
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        return $this->requestAsync('PROPFIND', $url, [
            'Depth' => $depth,
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body, $options)->then(function (ResponseInterface $response) use ($depth): array {
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
        });
    }

    /**
     * Run a REPORT {DAV:}sync-collection.
     *
     * @param string $url
     * @param array $properties
     * @param string $syncToken
     *
     * @return PromiseInterface<array>
     *
     * @see https://tools.ietf.org/html/rfc6578
     */
    public function syncCollectionAsync(string $url, array $properties, string $syncToken, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElementNS('DAV:', 'd:sync-collection'));

        $root->appendChild($dom->createElement('d:sync-token', $syncToken));
        $root->appendChild($dom->createElement('d:sync-level', '1'));

        $prop = $root->appendChild($dom->createElement('d:prop'));

        $namespaces = [
            'DAV:' => 'd',
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        return $this->requestAsync('REPORT', $url, [
            'Depth' => '0',
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body, $options)->then(function (ResponseInterface $response) {
            return $this->parseMultiStatus((string) $response->getBody());
        });
    }

    /**
     * Run a REPORT card:addressbook-multiget.
     *
     * @param string $url
     * @param array $properties
     * @param \ArrayAccess $contacts
     *
     * @return PromiseInterface<array>
     *
     * @see https://tools.ietf.org/html/rfc6352#section-8.7
     */
    public function addressbookMultigetAsync(string $url, array $properties, \ArrayAccess $contacts, array $options = []): PromiseInterface
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

        return $this->requestAsync('REPORT', $url, [
            'Depth' => '1',
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body, $options)->then(function (ResponseInterface $response) {
            return $this->parseMultiStatus((string) $response->getBody());
        });
    }

    /**
     * Run a REPORT card:addressbook-query.
     *
     * @param string $url
     * @param array $properties
     * @param \ArrayAccess $contacts
     *
     * @return PromiseInterface<array>
     *
     * @see https://tools.ietf.org/html/rfc6352#section-8.6
     */
    public function addressbookQueryAsync(string $url, array $properties, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElementNS(CardDAVPlugin::NS_CARDDAV, 'card:addressbook-query'));
        $dom->createAttributeNS('DAV:', 'd:e');

        $prop = $root->appendChild($dom->createElement('d:prop'));

        $namespaces = [
            'DAV:' => 'd',
            CardDAVPlugin::NS_CARDDAV => 'card',
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        return $this->requestAsync('REPORT', $url, [
            'Depth' => '1',
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body, $options)->then(function (ResponseInterface $response) {
            return $this->parseMultiStatus((string) $response->getBody());
        });
    }

    /**
     * Add properties to the prop object.
     *
     * Properties must follow:
     * - for a simple value
     * [
     *     '{namespace}value',
     * ]
     *
     * - for a more complex value element
     * [
     *     [
     *         'name' => '{namespace}value',
     *         'value' => 'content element',
     *         'attributes' => ['name' => 'value', ...],
     *     ]
     * ]
     *
     * @param \DOMDocument $dom
     * @param \DOMNode $prop
     * @param array $properties
     * @param array $namespaces
     *
     * @return void
     */
    private function fetchProperties(\DOMDocument $dom, \DOMNode $prop, array $properties, array $namespaces)
    {
        foreach ($properties as $property) {
            if (is_array($property)) {
                $propertyExt = $property;
                $property = $propertyExt['name'];
            }
            [$namespace, $elementName] = Service::parseClarkNotation($property);

            $value = Arr::get($namespaces, $namespace, null);
            if (! is_null($value)) {
                $element = $prop->appendChild($dom->createElement("$value:$elementName"));
            } else {
                $element = $prop->appendChild($dom->createElementNS($namespace, 'x:'.$elementName));
            }

            if (isset($propertyExt)) {
                if (isset($propertyExt['value']) && ! is_null($propertyExt['value'])) {
                    $element->nodeValue = $propertyExt['value'];
                }
                if (isset($propertyExt['attributes'])) {
                    foreach ($propertyExt['attributes'] as $name => $property) {
                        $element->appendChild($dom->createAttribute($name))->value = $property;
                    }
                }
            }
        }
    }

    /**
     * Get a WebDAV property.
     *
     * @param string $property
     * @param string $url
     *
     * @return string|array|null
     */
    public function getProperty(string $property, string $url = '')
    {
        return $this->getPropertyAsync($property, $url, [
            RequestOptions::SYNCHRONOUS => true,
        ])->wait();
    }

    /**
     * Get a WebDAV property.
     *
     * @param string $property
     * @param string $url
     *
     * @return PromiseInterface<string|array|null>
     */
    public function getPropertyAsync(string $property, string $url = ''): PromiseInterface
    {
        return $this->propfindAsync($url, [
            $property,
        ])->then(function (array $properties) use ($property) {
            if (! isset($properties[$property])) {
                return;
            }

            $prop = $properties[$property];

            if (is_string($prop)) {
                return $prop;
            } elseif (is_array($prop)) {
                $value = $prop[0];

                return is_string($value) ? $value : $prop;
            }

            return $prop;
        });
    }

    /**
     * Get a {DAV:}supported-report-set propfind.
     *
     * @return array
     */
    public function getSupportedReportSet(): array
    {
        return $this->getSupportedReportSetAsync([
            RequestOptions::SYNCHRONOUS => true,
        ])->wait();
    }

    /**
     * Get a {DAV:}supported-report-set propfind.
     *
     * @return PromiseInterface<array>
     */
    public function getSupportedReportSetAsync(array $options = []): PromiseInterface
    {
        $propName = '{DAV:}supported-report-set';

        return $this->propFindAsync('', [$propName], 0, $options)
        ->then(function (array $properties) use ($propName): array {
            return array_map(function ($supportedReportSet) {
                foreach ($supportedReportSet['value'] as $kind) {
                    if ($kind['name'] == '{DAV:}report') {
                        foreach ($kind['value'] as $type) {
                            return $type['name'];
                        }
                    }
                }
            }, $properties[$propName]);
        });
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
    public function propPatchAsync(string $url, array $properties): PromiseInterface
    {
        $propPatch = new PropPatch();
        $propPatch->properties = $properties;
        $xml = $this->xml->write(
            '{DAV:}propertyupdate',
            $propPatch
        );

        return $this->requestAsync('PROPPATCH', $url, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $xml)->then(function (ResponseInterface $response): bool {
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
                    throw new DavClientException('PROPPATCH failed. The following properties errored: '.implode(', ', $errorProperties));
                }
            }

            return true;
        });
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
    public function options(): array
    {
        $response = $this->request('OPTIONS');

        $dav = $response->getHeader('Dav');
        if (! $dav) {
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
     * @param string $method
     * @param string $url
     * @param string|null|resource|\Psr\Http\MessageStreamInterface $body
     * @param array $headers
     *
     * @return ResponseInterface
     *
     * @throws ClientException, in case a curl error occurred
     */
    public function request(string $method, string $url = '', array $headers = [], $body = null, array $options = []): ResponseInterface
    {
        return $this->client->send(new Request($method, $url, $headers, $body), $options);
    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * @param string $method
     * @param string $url
     * @param string|null|resource|\Psr\Http\MessageStreamInterface $body
     * @param array $headers
     *
     * @return PromiseInterface
     *
     * @throws ClientException, in case a curl error occurred
     */
    public function requestAsync(string $method, string $url = '', array $headers = [], $body = null, array $options = []): PromiseInterface
    {
        return $this->client->sendAsync(new Request($method, $url, $headers, $body), $options);
    }

    /**
     * Create.
     * @param array $requests
     * @param array $config
     *
     * @return PromisorInterface
     */
    public function requestPool(array $requests, array $config = []): PromisorInterface
    {
        return new Pool($this->client, $requests, $config);
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
     * @param string $body xml body
     * @return array
     */
    public function parseMultiStatus(string $body): array
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
