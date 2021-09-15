<?php

namespace App\Services\DavClient\Utils\Traits;

use Safe\Exceptions\NetworkException;

class ServiceUrlQuery
{
    /**
     * Get service url.
     *
     * @return string|null
     *
     * @see https://tools.ietf.org/html/rfc6352#section-11
     */
    public function execute(string $name, bool $https, string $baseUri): ?string
    {
        try {
            $host = \Safe\parse_url($baseUri, PHP_URL_HOST);
            $entry = $this->dns_get_record($name.'.'.$host, DNS_SRV);

            if ($entry) {
                $target = $this->getEntryValue($entry, 'target');
                $port = $this->getEntryValue($entry, 'port');
                if ($target) {
                    if (($port === 443 && $https) || ($port === 80 && ! $https)) {
                        $port = null;
                    }

                    return ($https ? 'https' : 'http').'://'.$target.(is_null($port) ? '' : ':'.$port);
                }
            }
        } catch (\Safe\Exceptions\UrlException $e) {
            // catch exception and return null
        } catch (\Safe\Exceptions\NetworkException $e) {
            // catch exception and return null
        }

        return null;
    }

    private function getEntryValue($entry, $name)
    {
        return isset($entry[0][$name]) ? $entry[0][$name] : null;
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
