<?php

namespace App\Domains\Contact\Dav\Web\Auth;

use App\Domains\Contact\Dav\Web\DAVACL\PrincipalBackend;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class AuthBackend implements BackendInterface
{
    /**
     * Authentication Realm.
     *
     * The realm is often displayed by browser clients when showing the
     * authentication dialog.
     */
    protected string $realm = 'sabre/dav';

    /**
     * Sets the authentication realm for this backend.
     */
    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }

    /**
     * Check Laravel authentication.
     */
    public function check(RequestInterface $request, ResponseInterface $response): array
    {
        if (! Auth::check()) {
            return [false, 'User is not authenticated'];
        }

        return [true, PrincipalBackend::getPrincipalUser(Auth::user())];
    }

    /**
     * This method is called when a user could not be authenticated, and
     * authentication was required for the current request.
     *
     * This gives you the opportunity to set authentication headers. The 401
     * status code will already be set.
     *
     * In this case of Bearer Auth, this would for example mean that the
     * following header needs to be set:
     *
     * $response->addHeader('WWW-Authenticate', 'Bearer realm=SabreDAV');
     *
     * Keep in mind that in the case of multiple authentication backends, other
     * WWW-Authenticate headers may already have been set, and you'll want to
     * append your own WWW-Authenticate header instead of overwriting the
     * existing one.
     */
    public function challenge(RequestInterface $request, ResponseInterface $response): void
    {
        $auth = new \Sabre\HTTP\Auth\Bearer(
            $this->realm,
            $request,
            $response
        );
        $auth->requireLogin();
    }
}
