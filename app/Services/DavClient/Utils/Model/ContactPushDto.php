<?php

namespace App\Services\DavClient\Utils\Model;

class ContactPushDto extends ContactUpdateDto
{
    /**
     * @var int
     */
    public $mode;

    /**
     * Create a new ContactPushDto.
     *
     * @param  string  $uri
     * @param  string  $etag
     * @param  string|resource  $card
     * @param  int  $mode
     */
    public function __construct(string $uri, string $etag, $card, int $mode)
    {
        parent::__construct($uri, $etag, $card);
        $this->mode = $mode;
    }
}
