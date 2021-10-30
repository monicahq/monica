<?php

namespace App\Http\Controllers\DAV;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Redirect all GET methods to settings page.
 */
class DAVRedirect extends ServerPlugin
{
    public function initialize(Server $server)
    {
        $server->on('method:GET', [$this, 'httpGet'], 500);
    }

    /**
     * This method intercepts GET requests to collections and returns the html.
     *
     * @param  RequestInterface  $request
     * @param  ResponseInterface  $response
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        $response->setStatus(302);
        $response->setHeader('Location', route('settings.dav'));

        return false;
    }
}
