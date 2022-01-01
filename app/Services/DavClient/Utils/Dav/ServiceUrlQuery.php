<?php

namespace App\Services\DavClient\Utils\Dav;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Collection;
use Http\Client\Exception\RequestException;

class ServiceUrlQuery
{
    /**
     * Get service url.
     *
     * @return string|null
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-11
     * @see https://datatracker.ietf.org/doc/html/rfc2782
     */
    public function execute(string $name, bool $https, string $baseUri, DavClient $client): ?string
    {
        try {
            $host = \Safe\parse_url($baseUri, PHP_URL_HOST);
        } catch (\Safe\Exceptions\UrlException $e) {
            return null;
        }

        $entries = $this->dns_get_record($name.'.'.$host, DNS_SRV);

        if ($entries && $entries->count() > 0) {
            $entries = collect($entries)
                ->groupBy('pri')
                ->sortKeys()
                ->first()
                ->sortByDesc('weight');

            foreach ($entries as $entry) {
                try {
                    return $this->getUri($entry, $https, $client);
                } catch (RequestException $e) {
                    // no exception
                }
            }
        }

        return null;
    }

    /**
     * Get uri from entry.
     *
     * @param  array  $entry
     * @param  bool  $https
     * @param  DavClient  $client
     * @return string
     *
     * @throws \Http\Client\Exception\RequestException
     */
    private function getUri(array $entry, bool $https, DavClient $client): string
    {
        $uri = (new Uri())
            ->withScheme($https ? 'https' : 'http')
            ->withPort($entry['port'])
            ->withHost($entry['target']);

        // Test connection
        $client->request('GET', $uri);

        return (string) $uri;
    }

    private function dns_get_record(string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false): ?Collection
    {
        error_clear_last();
        $result = \dns_get_record($hostname, $type, $authns, $addtl, $raw);
        if ($result === false) {
            return null;
        }

        return collect($result);
    }
}
