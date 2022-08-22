<?php

namespace App\Services\DavClient\Utils\Dav;

use Sabre\DAV\Xml\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Sabre\DAV\Xml\Request\PropPatch;
use GuzzleHttp\Psr7\Utils as GuzzleUtils;
use Illuminate\Http\Client\PendingRequest;
use Sabre\CardDAV\Plugin as CardDAVPlugin;

class DavClient
{
    /**
     * @var string|null
     */
    protected $baseUri;

    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * Set the base uri of client.
     *
     * @param  string  $uri
     * @return self
     */
    public function setBaseUri(string $uri): self
    {
        $this->baseUri = $uri;

        return $this;
    }

    /**
     * Set credentials.
     *
     * @param  string  $username
     * @param  string  $password
     * @return self
     */
    public function setCredentials(string $username, string $password): self
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Get current uri.
     *
     * @param  string|null  $path
     * @return string
     */
    public function path(?string $path = null): string
    {
        $uri = GuzzleUtils::uriFor($this->baseUri);

        return (string) (is_null($path) || empty($path) ? $uri : $uri->withPath((string) Str::of($path)->start('/')));
    }

    /**
     * Get a PendingRequest.
     *
     * @return PendingRequest
     */
    public function getRequest(): PendingRequest
    {
        $request = Http::withUserAgent('Monica DavClient '.config('monica.app_version').'/Guzzle');

        if (! is_null($this->username) && ! is_null($this->password)) {
            $request = $request->withBasicAuth($this->username, $this->password);
        }

        return $request;
    }

    /**
     * Follow rfc6764 to get carddav service url.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6764
     */
    public function getServiceUrl()
    {
        // first attempt on relative url
        $target = $this->standardServiceUrl('.well-known/carddav');

        if (! $target) {
            // second attempt on absolute root url
            $target = $this->standardServiceUrl('/.well-known/carddav');
        }

        if (! $target) {
            // third attempt for non standard server, like Google API
            $target = $this->nonStandardServiceUrl('/.well-known/carddav');
        }

        if (! $target) {
            // Get service name register (section 9.2)
            $target = app(ServiceUrlQuery::class)->execute('_carddavs._tcp', true, $this->path(), $this);
            if (is_null($target)) {
                $target = app(ServiceUrlQuery::class)->execute('_carddav._tcp', false, $this->path(), $this);
            }
        }

        return $target;
    }

    private function standardServiceUrl(string $url): ?string
    {
        // Get well-known register (section 9.1)
        $response = $this->getRequest()
            ->withoutRedirecting()
            ->get($this->path($url));

        $code = $response->status();
        if ($code === 301 || $code === 302) {
            return $response->header('Location');
        }

        if ($response->serverError()) {
            $response->throw();
        }

        return null;
    }

    private function nonStandardServiceUrl($url): ?string
    {
        $response = $this->getRequest()
            ->withoutRedirecting()
            ->send('PROPFIND', $this->path($url));

        $code = $response->status();
        if ($code === 301 || $code === 302) {
            return $this->path($response->header('Location'));
        }

        return null;
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
    public function propFind($properties, int $depth = 0, array $options = [], string $url = ''): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = self::addElementNS($dom, 'DAV:', 'd:propfind');
        $prop = self::addElement($dom, $root, 'd:prop');

        $namespaces = ['DAV:' => 'd'];

        self::fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        $response = $this->request('PROPFIND', $url, $body, ['Depth' => $depth], $options);

        $result = self::parseMultiStatus($response->body());

        // If depth was 0, we only return the top item value
        if ($depth === 0) {
            reset($result);
            $result = current($result);

            return Arr::get($result, 'properties.200', []);
        }

        return array_map(function ($statusList) {
            return Arr::get($statusList, 'properties.200', []);
        }, $result);
    }

    /**
     * Run a REPORT {DAV:}sync-collection.
     *
     * @param  string  $url
     * @param  array|string  $properties
     * @param  string  $syncToken
     * @return array
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6578
     */
    public function syncCollection($properties, string $syncToken, array $options = [], string $url = ''): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = self::addElementNS($dom, 'DAV:', 'd:sync-collection');

        self::addElement($dom, $root, 'd:sync-token', $syncToken);
        self::addElement($dom, $root, 'd:sync-level', '1');

        $prop = self::addElement($dom, $root, 'd:prop');

        $namespaces = ['DAV:' => 'd'];

        self::fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        $response = $this->request('REPORT', $url, $body, ['Depth' => '0'], $options);

        return self::parseMultiStatus($response->body());
    }

    /**
     * Run a REPORT card:addressbook-multiget.
     *
     * @param  array|string  $properties
     * @param  iterable  $contacts
     * @param  string  $url
     * @param  array  $options
     * @return array
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-8.7
     */
    public function addressbookMultiget($properties, iterable $contacts, array $options = [], string $url = ''): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = self::addElementNS($dom, CardDAVPlugin::NS_CARDDAV, 'card:addressbook-multiget');
        $dom->createAttributeNS('DAV:', 'd:e');

        $prop = self::addElement($dom, $root, 'd:prop');

        $namespaces = [
            'DAV:' => 'd',
            CardDAVPlugin::NS_CARDDAV => 'card',
        ];

        self::fetchProperties($dom, $prop, $properties, $namespaces);

        foreach ($contacts as $contact) {
            self::addElement($dom, $root, 'd:href', $contact);
        }

        $body = $dom->saveXML();

        $response = $this->request('REPORT', $url, $body, ['Depth' => '1'], $options);

        return self::parseMultiStatus($response->body());
    }

    /**
     * Run a REPORT card:addressbook-query.
     *
     * @param  string  $url
     * @param  array|string  $properties
     * @return array
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-8.6
     */
    public function addressbookQuery($properties, array $options = [], string $url = ''): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = self::addElementNS($dom, CardDAVPlugin::NS_CARDDAV, 'card:addressbook-query');
        $dom->createAttributeNS('DAV:', 'd:e');

        $prop = self::addElement($dom, $root, 'd:prop');

        $namespaces = [
            'DAV:' => 'd',
            CardDAVPlugin::NS_CARDDAV => 'card',
        ];

        self::fetchProperties($dom, $prop, $properties, $namespaces);

        $body = $dom->saveXML();

        $response = $this->request('REPORT', $url, $body, ['Depth' => '1'], $options);

        return self::parseMultiStatus($response->body());
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
    private static function fetchProperties(\DOMDocument $dom, \DOMNode $prop, $properties, array $namespaces)
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
     * @return array|string|null
     */
    public function getProperty(string $property, string $url = '', array $options = [])
    {
        $properties = $this->propfind($property, 0, $options, $url);

        if (($prop = Arr::get($properties, $property)) && is_array($prop)) {
            $value = $prop[0];

            if (is_string($value)) {
                $prop = $value;
            }
        }

        return $prop;
    }

    /**
     * Get a {DAV:}supported-report-set propfind.
     *
     * @param  array  $options
     * @return array
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3253#section-3.1.5
     */
    public function getSupportedReportSet(array $options = []): array
    {
        $propName = '{DAV:}supported-report-set';

        $properties = $this->propFind($propName, 0, $options);

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
     * @return bool
     *
     * @see https://datatracker.ietf.org/doc/html/rfc2518#section-12.13
     */
    public function propPatch(array $properties, string $url = ''): bool
    {
        $propPatch = new PropPatch();
        $propPatch->properties = $properties;
        $body = (new Service())->write(
            '{DAV:}propertyupdate',
            $propPatch
        );

        $response = $this->request('PROPPATCH', $url, $body);

        if ($response->status() === 207) {
            // If it's a 207, the request could still have failed, but the
            // information is hidden in the response body.
            $result = self::parseMultiStatus($response->body());

            $errorProperties = [];
            foreach ($result as $statusList) {
                foreach ($statusList['properties'] as $status => $properties) {
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
    }

    /**
     * Performs an HTTP options request.
     *
     * This method returns all the features from the 'DAV:' header as an array.
     * If there was no DAV header, or no contents this method will return an
     * empty array.
     *
     * @param  string  $url
     * @return array
     */
    public function options(string $url = ''): array
    {
        $response = $this->request('OPTIONS', $url);

        $dav = $response->header('Dav');
        if (empty($dav)) {
            return [];
        }
        $davs = explode(', ', $dav);

        return array_map(function ($header) {
            return trim($header);
        }, $davs);
    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  string|null|resource|\Psr\Http\Message\StreamInterface  $body
     * @param  array  $headers
     * @return Response
     */
    public function request(string $method, string $url = '', $body = null, array $headers = [], array $options = []): Response
    {
        $request = $this->getRequest()
            ->withHeaders($headers);

        if ($body !== null) {
            $request = $request->withBody($body, 'application/xml; charset=utf-8');
        }

        $url = Str::startsWith($url, 'http') ? $url : $this->path($url);

        return $request
            ->send($method, $url, $options)
            ->throw();
    }

    /**
     * Parses a WebDAV multistatus response body.
     *
     * This method returns an array with the following structure
     *
     * [
     *   'url/to/resource' => [
     *     'properties' => [
     *       '200' => [
     *          '{DAV:}property1' => 'value1',
     *          '{DAV:}property2' => 'value2',
     *       ],
     *       '404' => [
     *          '{DAV:}property1' => null,
     *          '{DAV:}property2' => null,
     *       ],
     *     ],
     *     'status' => 200,
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
    private static function parseMultiStatus(string $body): array
    {
        $multistatus = (new Service())
            ->expect('{DAV:}multistatus', $body);

        $result = [];

        if (is_object($multistatus)) {
            foreach ($multistatus->getResponses() as $response) {
                $result[$response->getHref()] = [
                    'properties' => $response->getResponseProperties(),
                    'status' => $response->getHttpStatus() ?? '200',
                ];
            }

            $synctoken = $multistatus->getSyncToken();
            if (! empty($synctoken)) {
                $result['synctoken'] = $synctoken;
            }
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
    private static function addElementNS(\DOMDocument $dom, ?string $namespace, string $qualifiedName): \DOMNode
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
    private static function addElement(\DOMDocument $dom, \DOMNode $root, string $name, ?string $value = null): \DOMNode
    {
        return $root->appendChild($dom->createElement($name, $value));
    }
}
