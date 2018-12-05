<?php

namespace App\Http\Controllers\CardDAV;

use Illuminate\Http\Request;
use Sabre\CardDAV\VCFExportPlugin;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Sabre\DAV\Server as SabreServer;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Barryvdh\Debugbar\Facade as Debugbar;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Models\CardDAV\MonicaAddressBookRoot;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use App\Models\CardDAV\Backends\MonicaAuthBackend;
use App\Models\CardDAV\Backends\MonicaCardDAVBackend;
use App\Models\CardDAV\Backends\MonicaPrincipalBackend;

class CardDAVController extends Controller
{
    private const BASE_URI = '/carddav/';

    /**
     * Display the specified resource.
     */
    public function init(Request $request)
    {
        // Disable debugger for caldav output
        if (config('app.debug')) {
            Debugbar::disable();
        }

        $server = $this->getServer($request);

        $this->addPlugins($server);

        return $this->server($server);
    }

    private function getNodes() : array
    {
        // Initiate custom backends for link between Sabre and Monica
        $principalBackend = new MonicaPrincipalBackend();   // User rights
        $carddavBackend = new MonicaCardDAVBackend();       // Contacts

        return [
            new PrincipalCollection($principalBackend),
            new MonicaAddressBookRoot($principalBackend, $carddavBackend),
        ];
    }

    private function getServer(Request $request)
    {
        $nodes = $this->getNodes();

        // Initiate Sabre server
        $server = new SabreServer($nodes);
        $server->sapi = new SapiServerMock();

        // Base Uri of carddav
        $server->setBaseUri(self::BASE_URI);
        // Set Url with trailing slash
        $server->httpRequest->setUrl($this->fullUrl($request));

        if (App::environment('testing')) {
            // Testing needs request to be set manually
            $server->httpRequest->setMethod($request->method());
            $server->httpRequest->setBody($request->getContent(true));
            $server->httpRequest->setHeaders($request->headers->all());
        }

        if (! App::environment('production')) {
            $server->debugExceptions = true;
        }

        return $server;
    }

    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl(Request $request)
    {
        $query = $request->getQueryString();
        $url = str_finish($request->getPathInfo(), '/');

        return $query ? $url.'?'.$query : $url;
    }

    /**
     * Add required plugins.
     */
    private function addPlugins(SabreServer $server)
    {
        // Authentication backend
        $authBackend = new MonicaAuthBackend();
        $server->addPlugin(new AuthPlugin($authBackend, 'SabreDAV'));

        // CardDAV plugin
        $server->addPlugin(new CardDAVPlugin());

        // ACL plugnin
        $aclPlugin = new AclPlugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $aclPlugin->hideNodesFromListings = true;
        $server->addPlugin($aclPlugin);

        // VCFExport
        $server->addPlugin(new VCFExportPlugin());

        // In local environment add browser plugin
        if (App::environment('local')) {
            $server->addPlugin(new BrowserPlugin());
        }
    }

    private function server(SabreServer $server)
    {
        // Execute sabre requests
        $server->exec();

        // Transform to Laravel response
        $content = $server->httpResponse->getBodyAsString();
        $status = $server->httpResponse->getStatus();
        $headers = $server->httpResponse->getHeaders();

        return response($content, $status)
            ->withHeaders($headers);
    }
}
