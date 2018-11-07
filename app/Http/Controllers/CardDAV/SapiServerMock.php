<?php

namespace App\Http\Controllers\CardDAV;

use Sabre\HTTP\Sapi;
use Sabre\HTTP\ResponseInterface;

/**
 * Mock version of SapiServer to avoid 'header()' calls.
 */
class SapiServerMock extends Sapi
{
    public static function sendResponse(ResponseInterface $response)
    {
    }
}
