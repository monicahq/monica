<?php

namespace App\Http\Middleware;

use Monicahq\Cloudflare\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * Get the trusted proxies.
     *
     * @return array|string|null
     */
    protected function proxies()
    {
        return config('app.trust_proxies') ?? $this->proxies;
    }
}
