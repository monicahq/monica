<?php

namespace App\Http\Controllers\CardDAV;

use Sabre\HTTP\Sapi;
use Sabre\HTTP\ResponseInterface;

class SapiServerMock extends Sapi
{
    public static function sendResponse(ResponseInterface $response)
    {
    }
}
