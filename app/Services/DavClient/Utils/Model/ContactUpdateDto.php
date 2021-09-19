<?php

namespace App\Services\DavClient\Utils\Model;

class ContactUpdateDto extends ContactDto
{
    /**
     * @var string|resource
     */
    public $card;

    /**
     * Create a new ContactUpdateDto.
     *
     * @param  string  $uri
     * @param  string  $etag
     * @param  string|resource  $card
     */
    public function __construct(string $uri, string $etag, $card)
    {
        $this->uri = $uri;
        $this->etag = $etag;
        $this->card = $card;
    }
}
