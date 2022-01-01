<?php

namespace App\Services\DavClient\Utils\Model;

class ContactPushDto extends ContactUpdateDto
{
    /**
     * @var int
     */
    public $mode;

    public const MODE_MATCH_NONE = 0;

    public const MODE_MATCH_ETAG = 1;

    public const MODE_MATCH_ANY = 2;

    /**
     * @var int
     */
    public $contactId;

    /**
     * Create a new ContactPushDto.
     *
     * @param  string  $uri
     * @param  string|null  $etag
     * @param  string|resource  $card
     * @param  int  $mode
     */
    public function __construct(string $uri, ?string $etag, $card, int $contact_id, int $mode = self::MODE_MATCH_NONE)
    {
        parent::__construct($uri, $etag, $card);
        $this->mode = $mode;
        $this->contactId = $contact_id;
    }
}
