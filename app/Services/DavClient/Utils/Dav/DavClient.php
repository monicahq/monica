<?php

namespace App\Services\DavClient\Utils\Dav;

use GuzzleHttp\Pool;
use Sabre\DAV\Xml\Service;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\App;
use Sabre\DAV\Xml\Request\PropPatch;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Services\DavClient\Utils\Traits\ServiceUrlQuery;

class DavClient
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
     * @param  array  $settings
     * @param  GuzzleClient  $client
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
            'verify' => ! App::environment('local'),
        ]) : $client;

        $this->xml = new Service();
    }

    /**
     * Follow rfc6764 to get carddav service url.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6764
     */
    public function getServiceUrl()
    {
        $target = $this->standardServiceUrl();

        if (! $target) {
            // second attempt for non standard server, like Google API
            $target = $this->nonStandardServiceUrl();
        }

        if (! $target) {
            // Get service name register (section 9.2)
            $target = app(ServiceUrlQuery::class)->execute('_carddavs._tcp', true, $this->getBaseUri());
            if (is_null($target)) {
                $target = app(ServiceUrlQuery::class)->execute('_carddav._tcp', false, $this->getBaseUri());
            }
        }

        return $target;
    }

    private function standardServiceUrl(): ?string
    {
        // Get well-known register (section 9.1)
        $wkUri = $this->getBaseUri('/.well-known/carddav');

        try {
            $response = $this->requestAsync('GET', $wkUri, [], null, [
                RequestOptions::ALLOW_REDIRECTS => false,
                RequestOptions::SYNCHRONOUS => true,
            ])->wait();

            $code = $response->getStatusCode();
            if (($code === 301 || $code === 302) && $response->hasHeader('Location')) {
                return $response->getHeader('Location')[0];
            }
        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                $code = $e->getResponse()->getStatusCode();
                if ($code !== 400 && $code !== 401 && $code !== 404) {
                    throw $e;
                }
            }
        }

        return null;
    }

    private function nonStandardServiceUrl(): ?string
    {
        $wkUri = $this->getBaseUri('/.well-known/carddav');

        try {
            $response = $this->requestAsync('PROPFIND', $wkUri, [], null, [
                RequestOptions::ALLOW_REDIRECTS => false,
                RequestOptions::SYNCHRONOUS => true,
            ])->wait();

            $code = $response->getStatusCode();
            if (($code === 301 || $code === 302) && $response->hasHeader('Location')) {
                $location = $response->getHeader('Location')[0];

                return $this->getBaseUri($location);
            }
        } catch (ClientException $e) {
            // catch exception and return null
        }

        return null;
    }

    /**
     * Get current uri.
     *
     * @param  string|null  $path
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
     * @param  string  $uri
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
     * @param  string  $url
     * @param  array|string  $properties
     * @param  int  $depth
     * @return array
     */
    public function propFind(string $url, $properties, int $depth = 0): array
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
     * @param  string  $url
     * @param  array|string  $properties
     * @param  int  $depth
     * @return PromiseInterface
     */
    public function propFindAsync(string $url, $properties, int $depth = 0, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $this->addElementNS($dom, 'DAV:', 'd:propfind');
        $prop = $this->addElement($dom, $root, 'd:prop');

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

            // If depth was 0, we only return the top item value
            if ($depth === 0) {
                reset($result);
                $result = current($result);

                return Arr::get($result, 200, []);
            }

            return array_map(function ($statusList) {
                return Arr::get($statusList, 200, []);
            }, $result);
        });
    }

    /**
     * Run a REPORT {DAV:}sync-collection.
     *
     * @param  string  $url
     * @param  array|string  $properties
     * @param  string  $syncToken
     * @return PromiseInterface
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6578
     */
    public function syncCollectionAsync(string $url, $properties, string $syncToken, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $this->addElementNS($dom, 'DAV:', 'd:sync-collection');

        $this->addElement($dom, $root, 'd:sync-token', $syncToken);
        $this->addElement($dom, $root, 'd:sync-level', '1');

        $prop = $this->addElement($dom, $root, 'd:prop');

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
     * @param  string  $url
     * @param  array|string  $properties
     * @param  iterable  $contacts
     * @return PromiseInterface
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-8.7
     */
    public function addressbookMultigetAsync(string $url, $properties, iterable $contacts, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $this->addElementNS($dom, CardDAVPlugin::NS_CARDDAV, 'card:addressbook-multiget');
        $dom->createAttributeNS('DAV:', 'd:e');

        $prop = $this->addElement($dom, $root, 'd:prop');

        $namespaces = [
            'DAV:' => 'd',
            CardDAVPlugin::NS_CARDDAV => 'card',
        ];

        $this->fetchProperties($dom, $prop, $properties, $namespaces);

        foreach ($contacts as $contact) {
            $this->addElement($dom, $root, 'd:href', $contact);
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
     * @param  string  $url
     * @param  array|string  $properties
     * @return PromiseInterface
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-8.6
     */
    public function addressbookQueryAsync(string $url, $properties, array $options = []): PromiseInterface
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $this->addElementNS($dom, CardDAVPlugin::NS_CARDDAV, 'card:addressbook-query');
        $dom->createAttributeNS('DAV:', 'd:e');

        $prop = $this->addElement($dom, $root, 'd:prop');

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
     * @param  \DOMDocument  $dom
     * @param  \DOMNode  $prop
     * @param  array|string  $properties
     * @param  array  $namespaces
     * @return void
     */
    private function fetchProperties(\DOMDocument $dom, \DOMNode $prop, $properties, array $namespaces)
    {
        if (is_string($properties)) {
            $properties = [$properties];
        }

        foreach ($properties as $property) {
            if (is_array($property)) {
                $propertyExt = $property;
                $property = $propertyExt['name'];
            }
            [$namespace, $elementName] = Service::parseClarkNotation($property);

            $ns = Arr::get($namespaces, $namespace);
            $element = $ns !== null
                ? $dom->createElement("$ns:$elementName")
                : $dom->createElementNS($namespace, "x:$elementName");

            $child = $prop->appendChild($element);

            if (isset($propertyExt)) {
                if (($nodeValue = Arr::get($propertyExt, 'value')) !== null) {
                    $child->nodeValue = $nodeValue;
                }
                if (($attributes = Arr::get($propertyExt, 'attributes')) !== null) {
                    foreach ($attributes as $name => $property) {
                        $child->appendChild($dom->createAttribute($name))->nodeValue = $property;
                    }
                }
            }
        }
    }

    /**
     * Get a WebDAV property.
     *
     * @param  string  $property
     * @param  string  $url
     * @return string|array<array>|null
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
     * @param  string  $property
     * @param  string  $url
     * @return PromiseInterface
     */
    public function getPropertyAsync(string $property, string $url = '', array $options = []): PromiseInterface
    {
        return $this->propfindAsync($url, $property, 0, $options)
        ->then(function (array $properties) use ($property) {
            if (($prop = Arr::get($properties, $property))
                && is_array($prop)) {
                $value = $prop[0];

                if (is_string($value)) {
                    $prop = $value;
                }
            }

            return $prop;
        });
    }

    /**
     * Get a {DAV:}supported-report-set propfind.
     *
     * @return array
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3253#section-3.1.5
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
     * @param  array  $options
     * @return PromiseInterface
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3253#section-3.1.5
     */
    public function getSupportedReportSetAsync(array $options = []): PromiseInterface
    {
        $propName = '{DAV:}supported-report-set';

        return $this->propFindAsync('', $propName, 0, $options)
        ->then(function (array $properties) use ($propName): array {
            if (($prop = Arr::get($properties, $propName)) && is_array($prop)) {
                $prop = array_map(function ($supportedReport) {
                    return $this->iterateOver($supportedReport, '{DAV:}supported-report', function ($report) {
                        return $this->iterateOver($report, '{DAV:}report', function ($type) {
                            return Arr::get($type, 'name');
                        });
                    });
                }, $prop);
            }

            return $prop;
        });
    }

    /**
     * Iterate over the list, if it contains an item name that match with $name.
     *
     * @param  array  $list
     * @param  string  $name
     * @param  callable  $callback
     * @return mixed
     */
    private function iterateOver(array $list, string $name, callable $callback)
    {
        if (Arr::get($list, 'name') === $name
            && ($value = Arr::get($list, 'value'))) {
            foreach ($value as $item) {
                return $callback($item);
            }
        }
    }

    /**
     * Updates a list of properties on the server.
     *
     * The list of properties must have clark-notation properties for the keys,
     * and the actual (string) value for the value. If the value is null, an
     * attempt is made to delete the property.
     *
     * @param  string  $url
     * @param  array  $properties
     * @return PromiseInterface
     *
     * @see https://datatracker.ietf.org/doc/html/rfc2518#section-12.13
     */
    public function propPatchAsync(string $url, array $properties): PromiseInterface
    {
        $propPatch = new PropPatch();
        $propPatch->properties = $properties;
        $body = $this->xml->write(
            '{DAV:}propertyupdate',
            $propPatch
        );

        return $this->requestAsync('PROPPATCH', $url, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ], $body)->then(function (ResponseInterface $response): bool {
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
                if (! empty($errorProperties)) {
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
     * @param  string  $method
     * @param  string  $url
     * @param  string|null|resource|\Psr\Http\Message\StreamInterface  $body
     * @param  array  $headers
     * @return ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\ClientException in case a curl error occurred
     */
    public function request(string $method, string $url = '', array $headers = [], $body = null, array $options = []): ResponseInterface
    {
        return $this->client->send(new Request($method, $url, $headers, $body), $options);
    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  string|null|resource|\Psr\Http\Message\StreamInterface  $body
     * @param  array  $headers
     * @return PromiseInterface
     *
     * @throws \GuzzleHttp\Exception\ClientException in case a curl error occurred
     */
    public function requestAsync(string $method, string $url = '', array $headers = [], $body = null, array $options = []): PromiseInterface
    {
        return $this->client->sendAsync(new Request($method, $url, $headers, $body), $options);
    }

    /**
     * Create multiple request in parallel.
     *
     * @param  array  $requests
     * @param  array  $config
     * @return PromiseInterface
     */
    public function requestPool(array $requests, array $config = []): PromiseInterface
    {
        return (new Pool($this->client, $requests, $config))->promise();
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
     * @param  string  $body  xml body
     * @return array
     *
     * @see https://datatracker.ietf.org/doc/html/rfc4918#section-9.2.1
     */
    private function parseMultiStatus(string $body): array
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

    /**
     * Create a new Element Namespace and add it as document's child.
     *
     * @param  \DOMDocument  $dom
     * @param  string|null  $namespace
     * @param  string  $qualifiedName
     * @return \DOMNode
     */
    private function addElementNS(\DOMDocument $dom, ?string $namespace, string $qualifiedName): \DOMNode
    {
        return $dom->appendChild($dom->createElementNS($namespace, $qualifiedName));
    }

    /**
     * Create a new Element and add it as root's child.
     *
     * @param  \DOMDocument  $dom
     * @param  \DOMNode  $root
     * @param  string  $name
     * @param  string|null  $value
     * @return \DOMNode
     */
    private function addElement(\DOMDocument $dom, \DOMNode $root, string $name, ?string $value = null): \DOMNode
    {
        return $root->appendChild($dom->createElement($name, $value));
    }
}
