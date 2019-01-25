<?php

namespace App\Http\Controllers\DAV;

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
