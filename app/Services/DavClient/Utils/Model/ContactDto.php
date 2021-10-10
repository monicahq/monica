<?php

namespace App\Services\DavClient\Utils\Model;

class ContactDto
{
    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $etag;

    /**
     * Create a new ContactDto.
     *
     * @param  string  $uri
     * @param  string  $etag
     */
    public function __construct(string $uri, string $etag)
    {
        $this->uri = $uri;
        $this->etag = $etag;
    }
}
