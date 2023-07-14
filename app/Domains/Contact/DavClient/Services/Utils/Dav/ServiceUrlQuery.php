<?php

namespace App\Domains\Contact\DavClient\Services\Utils\Dav;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Safe\Exceptions\UrlException;
use function Safe\parse_url;

class ServiceUrlQuery
{
    /**
     * Get service url.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-11
     * @see https://datatracker.ietf.org/doc/html/rfc2782
     */
    public function execute(string $name, bool $https, string $baseUri, DavClient $client): ?string
    {
        if (($host = $this->parseUrl($baseUri)) !== null) {
            $entries = Http::getDnsRecord($name.'.'.$host, DNS_SRV);

            if (optional($entries)->count()) {
                $entries = $entries
                    ->groupBy('pri')
                    ->sortKeys()
                    ->sortByDesc('weight')
                    ->flatten(1);

                foreach ($entries as $entry) {
                    try {
                        return $this->getUri($entry, $https, $client);
                    } catch (RequestException $e) {
                        // if any exception occurs, it will try the next entry.
                    }
                }
            }
        }

        return null;
    }

    private function parseUrl(string $baseUri): ?string
    {
        try {
            return parse_url($baseUri, PHP_URL_HOST);
        } catch (UrlException $e) {
            return null;
        }
    }

    /**
     * Get uri from entry.
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
}
