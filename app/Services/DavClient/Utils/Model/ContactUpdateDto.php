<?php

namespace App\Services\DavClient\Utils\Model;

use function Safe\fclose;
use function Safe\stream_get_contents;

class ContactUpdateDto extends ContactDto
{
    /**
     * @var string
     */
    public $card;

    /**
     * Create a new ContactUpdateDto.
     *
     * @param  string  $uri
     * @param  string|null  $etag
     * @param  string|resource  $card
     */
    public function __construct(string $uri, ?string $etag, $card)
    {
        parent::__construct($uri, $etag);
        $this->card = self::transformCard($card);
    }

    /**
     * Transform card.
     *
     * @param  string|resource  $card
     * @return string
     */
    protected static function transformCard($card): string
    {
        if (is_resource($card)) {
            $card = tap(stream_get_contents($card), function () use ($card) {
                fclose($card);
            });
        }

        return $card;
    }
}
