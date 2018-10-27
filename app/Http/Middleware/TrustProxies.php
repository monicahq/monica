<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * Sets the trusted proxies on the request to the value of trustedproxy.proxies.
     *
     * @param \Illuminate\Http\Request $request
     */
    protected function setTrustedProxyIpAddresses(Request $request)
    {
        if (! $this->proxies) {
            $trustedIps = $this->config->get('trustedproxy.proxies');
            // check for a comma separated list of proxies
            if (! is_array($trustedIps) && str_contains($trustedIps, ',')) {
                $this->proxies = array_map('trim', explode(',', $trustedIps));
            }
        }
        parent::setTrustedProxyIpAddresses($request);
    }
}
