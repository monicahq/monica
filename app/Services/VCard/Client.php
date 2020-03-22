<?php

namespace App\Services\VCard;

use Sabre\DAV\Xml\Service;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use Sabre\DAV\Xml\Request\PropPatch;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

/**
 * SabreDAV DAV client.
 *
 * This client wraps around Curl to provide a convenient API to a WebDAV
 * server.
 *
 * NOTE: This class is experimental, it's api will likely change in the future.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Client
{
    /**
     * The xml service.
     *
     * Uset this service to configure the property and namespace maps.
     *
     * @var mixed
     */
    public $xml;

    /**
     * @var GuzzleClient
     */
    protected $client;


    /**
     * Constructor.
     *
     * Settings are provided through the 'settings' argument. The following
     * settings are supported:
     *
     *   * baseUri
     *   * userName (optional)
     *   * password (optional)
     *   * proxy (optional)
     *   * authType (optional)
     *   * encoding (optional)
     *
     *  authType must be a bitmap, using self::AUTH_BASIC, self::AUTH_DIGEST
     *  and self::AUTH_NTLM. If you know which authentication method will be
     *  used, it's recommended to set it, as it will save a great deal of
     *  requests to 'discover' this information.
     *
     *  Encoding is a bitmap with one of the ENCODING constants.
     *
     * @param array $settings
     * @param GuzzleClient $client
     */
    public function __construct(array $settings, GuzzleClient $client = null)
    {
        if (is_null($client) && !isset($settings['base_uri'])) {
            throw new \InvalidArgumentException('A baseUri must be provided');
        }

        //parent::__construct();

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
        $baseUri = $this->client->getConfig('base_uri')->withPath('/.well-known/carddav');

        $response = $this->client->get($baseUri, ['allow_redirects' => false]);

        $code = $response->getStatusCode(); // 200

        if (($code === 301 || $code === 302) && $response->hasHeader('Location')) {
            return $response->getHeader('Location')[0];
        }
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

        $request = new Request('PROPFIND', $url, [
            'Depth' => $depth,
            'Content-Type' => 'application/xml',
        ], $body);

        $response = $this->client->send($request);

        /*
        if ($response->getStatusCode() >= 400) {
            throw new ClientException($response);
        }
        */

        $result = $this->parseMultiStatus((string) $response->getBody());

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

    public function getProperty(string $property, string $url = '')
    {
        $propfind = $this->propfind($url, [
            $property,
        ]);

        if (!isset($propfind[$property])) {
            return null;
        }

        return $propfind[$property][0]['value'];
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

        //$url = $this->getAbsoluteUrl($url);
        $request = new Request('PROPPATCH', $url, [
            'Content-Type' => 'application/xml',
        ], $xml);
        $response = $this->client->send($request);

        /*
        if ($response->getStatus() >= 400) {
            throw new HTTP\ClientHttpException($response);
        }
        */

        if (207 === $response->getStatusCode()) {
            // If it's a 207, the request could still have failed, but the
            // information is hidden in the response body.
            $result = $this->parseMultiStatus($response->getBody());

            $errorProperties = [];
            foreach ($result as $href => $statusList) {
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
        $request = new Request('OPTIONS', ''/*, $this->getAbsoluteUrl('')*/);
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
        //$url = $this->getAbsoluteUrl($url);

        $response = $this->client->send(new Request($method, $url, $headers, $body));

        return [
            'body' => $response->getBody(),
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

        return $result;
    }
}
