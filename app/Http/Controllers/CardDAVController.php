<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Barryvdh\Debugbar\Facade as Debugbar;
use App\Models\CardDAV\MonicaAddressBookRoot;
use App\Models\CardDAV\Backends\MonicaSabreBackend;
use App\Models\CardDAV\Backends\MonicaCardDAVBackend;
use App\Models\CardDAV\Backends\MonicaPrincipleBackend;

class CardDAVController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function init(Request $request)
    {
        // Disable debugger for caldav output
        Debugbar::disable();

        // Initiate custom backends for link between Sabre and Monica
        $authBackend = new MonicaSabreBackend();            // Authentication
        $principalBackend = new MonicaPrincipleBackend();   // User rights
        $carddavBackend = new MonicaCardDAVBackend();       // Contacts

        $nodes = [
                new \Sabre\DAVACL\PrincipalCollection($principalBackend),
                new MonicaAddressBookRoot($principalBackend, $carddavBackend),
        ];

        // Initiate Sabre server
        $server = new \Sabre\DAV\Server($nodes);
        $server->setBaseUri('/carddav');
        $server->debugExceptions = true;

        // Modify Laravel request to include trailing slash. Laravel removes it by default, Sabre needs it.
        $server->httpRequest->setUrl(str_replace('/carddav', '/carddav/', $request->getRequestUri()));
        $server->httpRequest->setBaseUrl('/carddav/');

        // Testing needs method verb to be set manually
        if (App::environment('testing')) {
            $server->httpRequest->setMethod($request->method());
        }

        // Add required plugins
        $server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'SabreDAV'));

        // CardDAV plugin
        $server->addPlugin(new \Sabre\CardDAV\Plugin());

        // ACL plugnin
        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($aclPlugin);

        // In debug mode add browser plugin
        if (App::environment('local')) {
            $server->addPlugin(new \Sabre\DAV\Browser\Plugin());
        }

        // Execute requests and catch output
        // We do this because laravel always sends a 200 back, but we need to use the StatusCode and of Sabre
        ob_start();
        $server->exec();
        $status = $server->httpResponse->getStatus();
        $content = ob_get_contents();
        $headers = $server->httpResponse->getHeaders();
        ob_end_clean();

        // Return response through laravel
        Log::debug(__CLASS__.' init', [
            'status' => $status,
            'content' => $content,
        ]);

        return response($content, $status)
            ->withHeaders($headers);
    }
}
