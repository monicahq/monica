<?php

namespace App\Services\DavClient\Utils\Dav;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Arr;
use Safe\Exceptions\NetworkException;

class ServiceUrlQuery
{
    /**
     * Get service url.
     *
     * @return string|null
     *
     * @see https://datatracker.ietf.org/doc/html/rfc6352#section-11
     */
    public function execute(string $name, bool $https, string $baseUri): ?string
    {
        try {
            $host = \Safe\parse_url($baseUri, PHP_URL_HOST);
            $entry = $this->dns_get_record($name.'.'.$host, DNS_SRV);

            if ($entry && $target = Arr::get($entry, '0.target')) {
                $uri = (new Uri())
                    ->withScheme($https ? 'https' : 'http')
                    ->withPort(Arr::get($entry, '0.port'))
                    ->withHost($target);

                return (string) $uri;
            }
        } catch (\Safe\Exceptions\UrlException $e) {
            // catch exception and return null
        } catch (\Safe\Exceptions\NetworkException $e) {
            // catch exception and return null
        }

        return null;
    }

    private function dns_get_record(string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false): array
    {
        error_clear_last();
        $result = \dns_get_record($hostname, $type, $authns, $addtl, $raw);
        if ($result === false) {
            throw NetworkException::createFromPhpError();
        }

        return $result;
    }
}
