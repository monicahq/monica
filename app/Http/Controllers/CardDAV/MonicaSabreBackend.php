<?php

namespace App\Http\Controllers\CardDAV;

use Sabre\DAV, Sabre\CalDAV, Sabre\DAVACL;
use Auth;
use Log;

class MonicaSabreBackend implements \Sabre\DAV\Auth\Backend\BackendInterface {

    /**
     * When this method is called, the backend must check if authentication was
     * successful.
     *
     * The returned value must be one of the following
     *‹
     * [true, "principals/username"]
     * [false, "reason for failure"]
     *
     * If authentication was successful, it's expected that the authentication
     * backend returns a so-called principal url.
     *
     * Examples of a principal url:
     *
     * principals/admin
     * principals/user1
     * principals/users/joe
     * principals/uid/‹123457
     *
     * If you don't use WebDAV ACL (RFC3744) we recommend that you simply
     * return a string such as:
     *
     * principals/users/[username]
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    function check(\Sabre\HTTP\RequestInterface $request, \Sabre\HTTP\ResponseInterface $response) {
        Log::debug(__CLASS__.' check', func_get_args());
        if (Auth::check() || Auth::attempt(['email' => $request->getRawServerValue('PHP_AUTH_USER'), 'password' => $request->getRawServerValue('PHP_AUTH_PW')])) {
            Log::debug(__CLASS__.' check auth', [Auth::check()]);
            return [true, "principals/user".Auth::user()->id];
        }

        return [false, "Unknown user or password"];
    }

    /**
     * This method is called when a user could not be authenticated, and
     * authentication was required for the current request.
     *
     * This gives you the opportunity to set authentication headers. The 401
     * status code will already be set.
     *
     * In this case of Basic Auth, this would for example mean that the
     * following header needs to be set:
     *
     * $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
     *
     * Keep in mind that in the case of multiple authentication backends, other
     * WWW-Authenticate headers may already have been set, and you'll want to
     * append your own WWW-Authenticate header instead of overwriting the
     * existing one.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    function challenge(\Sabre\HTTP\RequestInterface $request, \Sabre\HTTP\ResponseInterface $response) {
        Log::debug(__CLASS__.' challenge', func_get_args());
        $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
    }

}