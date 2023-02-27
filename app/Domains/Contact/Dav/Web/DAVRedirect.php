<?php

namespace App\Domains\Contact\Dav\Web;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Redirect all GET methods to settings page.
 */
class DAVRedirect extends ServerPlugin
{
    public function initialize(Server $server): void
    {
        $server->on('method:GET', [$this, 'httpGet'], 500);
    }

    /**
     * This method intercepts GET requests to collections and returns the html.
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response): bool
    {
        $response->setStatus(302);
        $response->setHeader('Location', route('home'));

        return false;
    }
}
