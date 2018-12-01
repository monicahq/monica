<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace App\Models\CardDAV\Backends;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Auth\Backend\BackendInterface;

class MonicaAuthBackend implements BackendInterface
{
    /**
     * Authentication Realm.
     *
     * The realm is often displayed by browser clients when showing the
     * authentication dialog.
     *
     * @var string
     */
    protected $realm = 'sabre/dav';

    /**
     * Sets the authentication realm for this backend.
     *
     * @param string $realm
     * @return void
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * Check Laravel authentication.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    public function check(RequestInterface $request, ResponseInterface $response)
    {
        if (! Auth::check()) {
            return [false, 'User is not authenticated'];
        }

        return [true, MonicaPrincipalBackend::getPrincipalUser()];
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
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function challenge(RequestInterface $request, ResponseInterface $response)
    {
        $auth = new \Sabre\HTTP\Auth\Bearer(
            $this->realm,
            $request,
            $response
        );
        $auth->requireLogin();
    }
}
