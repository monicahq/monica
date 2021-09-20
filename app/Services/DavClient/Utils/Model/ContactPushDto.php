<?php

namespace App\Services\DavClient\Utils\Model;

use GuzzleHttp\Psr7\Request;

class ContactPushDto extends ContactDto
{
    /**
     * @var Request
     */
    public $request;

    /**
     * Create a new ContactPushDto.
     *
     * @param  string  $uri
     * @param  string  $etag
     * @param  Request  $request
     */
    public function __construct(string $uri, string $etag, Request $request)
    {
        $this->uri = $uri;
        $this->etag = $etag;
        $this->request = $request;
    }
}
