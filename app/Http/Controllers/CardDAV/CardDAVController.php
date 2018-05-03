<?php

namespace App\Http\Controllers\CardDAV;

use App\Http\Controllers\Controller;

// https://github.com/sabre-io/dav/blob/master/tests/Sabre/DAVACL/PrincipalBackend/AbstractPDOTest.php
// https://github.com/sabre-io/dav/blob/master/lib/DAVACL/PrincipalBackend/PDO.php
// https://github.com/sabre-io/dav/tree/master/lib/DAV/Browser
// https://github.com/sabre-io/dav/blob/master/lib/CardDAV/Backend/PDO.php
// http://sabre.io/dav/caldav-carddav-integration-guide/

use Illuminate\Support\Facades\Auth;

class CardDAVController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function init()
    {
        // TODO: Not sure if this is needed, check later
        date_default_timezone_set('Europe/Berlin');

        // Initiate custom backends for link between Sabra and Monica
        $authBackend = new MonicaSabreBackend();            // Authentication
        $principalBackend = new MonicaPrincipleBackend();   // User rights
        $carddavBackend = new MonicaCardDAVBackend();       // Contacts

        $nodes = [
                // Not sure if this is needed
                //new \Sabre\DAVACL\PrincipalCollection($principalBackend),
                new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend)
        ];

        // Initiate Sabre server
        $server = new \Sabre\DAV\Server($nodes);
        $server->setBaseUri('/carddav');

        // Add required plugins
        $server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'SabreDAV'));
        //$server->addPlugin(new \Sabre\DAV\Browser\Plugin());
        $server->addPlugin(new \Sabre\CardDAV\Plugin());
        $server->addPlugin(new \Sabre\DAVACL\Plugin());
        $server->addPlugin(new \Sabre\DAV\Sync\Plugin());

        // Execute requests and catch output
        // We do this because laravel always sends a 200 back, but we need to statuscode of Sabre
        ob_start();
        $server->exec();
        $status = $server->httpResponse->getStatus();
        $content = ob_get_contents();
        ob_end_clean();

        // Return response through laravel
        return response($content, $status);
    }
}
