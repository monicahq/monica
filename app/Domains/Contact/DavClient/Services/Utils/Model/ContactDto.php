<?php

namespace App\Domains\Contact\DavClient\Services\Utils\Model;

class ContactDto
{
    /**
     * Create a new ContactDto.
     */
    public function __construct(
        public string $uri,
        public ?string $etag = null
    ) {}
}
