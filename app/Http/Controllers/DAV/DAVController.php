<?php

namespace App\Http\Controllers\DAV;

use Illuminate\Http\Request;
use Sabre\CalDAV\CalendarRoot;
use Sabre\CalDAV\ICSExportPlugin;
use Sabre\CardDAV\VCFExportPlugin;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Sabre\DAV\Server as SabreServer;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Sync\Plugin as SyncPlugin;
use Barryvdh\Debugbar\Facade as Debugbar;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Http\Controllers\DAV\Auth\AuthBackend;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use App\Http\Controllers\DAV\DAVACL\PrincipalBackend;
use App\Http\Controllers\DAV\Backend\CalDAV\CalDAVBackend;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Http\Controllers\DAV\Backend\CardDAV\AddressBookRoot;

class DAVController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function init(Request $request)
    {
        if (! config('dav.enabled')) {
            abort(404);
        }

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
        $principalBackend = new PrincipalBackend();   // User rights
        $carddavBackend = new CardDAVBackend();       // Contacts
        $caldavBackend = new CalDAVBackend();         // Calendar

        return [
            new PrincipalCollection($principalBackend),
            new AddressBookRoot($principalBackend, $carddavBackend),
            new CalendarRoot($principalBackend, $caldavBackend),
        ];
    }

    private function getServer(Request $request)
    {
        $nodes = $this->getNodes();

        // Initiate Sabre server
        $server = new SabreServer($nodes);
        $server->sapi = new SapiServerMock();

        // Base Uri of carddav
        $server->setBaseUri($this->getBaseUri());
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
        $authBackend = new AuthBackend();
        $server->addPlugin(new AuthPlugin($authBackend));

        // CardDAV plugin
        $server->addPlugin(new CardDAVPlugin());
        $server->addPlugin(new VCFExportPlugin());

        // CalDAV plugin
        $server->addPlugin(new CalDAVPlugin());
        $server->addPlugin(new ICSExportPlugin());

        // Sync Plugin - rfc6578
        $server->addPlugin(new SyncPlugin());

        // ACL plugnin
        $aclPlugin = new AclPlugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $aclPlugin->hideNodesFromListings = true;
        $server->addPlugin($aclPlugin);

        // In local environment add browser plugin
        if (App::environment('local')) {
            $server->addPlugin(new BrowserPlugin(false));
        } else {
            $server->addPlugin(new DAVRedirect());
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

    private function getBaseUri()
    {
        return str_start(str_finish(config('dav.path'), '/'), '/');
    }
}
