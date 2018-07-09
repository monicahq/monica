<?php

namespace App\Models\CardDAV\Backends;

use Sabre\DAV, Sabre\CalDAV, Sabre\DAVACL;
use Auth;
use Log;

class MonicaSabreBackend extends \Sabre\DAV\Auth\Backend\AbstractBasic {

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password) {
        $attempt = Auth::attempt(['email' => $username, 'password' => $password]);

        Log::debug(__CLASS__.' validateUserPass', [$attempt]);

        return $attempt;
    }

}
