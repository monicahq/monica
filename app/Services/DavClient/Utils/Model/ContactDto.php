<?php

namespace App\Services\DavClient\Utils\Model;

class ContactDto
{
    /**
     * @var string
     */
    public $uri;

    /**
     * @var string|null
     */
    public $etag;

    /**
     * Create a new ContactDto.
     *
     * @param  string  $uri
     * @param  string|null  $etag
     */
    public function __construct(string $uri, ?string $etag = null)
    {
        $this->uri = $uri;
        $this->etag = $etag;
    }
}
