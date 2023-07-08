<?php

namespace App\Domains\Contact\DavClient\Services\Utils\Model;

use function Safe\fclose;
use function Safe\stream_get_contents;

class ContactPushDto extends ContactDto
{
    public const MODE_MATCH_NONE = 0;

    public const MODE_MATCH_ETAG = 1;

    public const MODE_MATCH_ANY = 2;

    public string $card;

    /**
     * Create a new ContactPushDto.
     *
     * @param  string|resource  $card
     */
    public function __construct(
        string $uri,
        ?string $etag,
        mixed $card,
        public string $contactId,
        public int $mode = self::MODE_MATCH_NONE
    ) {
        parent::__construct($uri, $etag);
        $this->card = self::transformCard($card);
    }

    /**
     * Transform card.
     *
     * @param  string|resource  $card
     */
    protected static function transformCard($card): string
    {
        if (is_resource($card)) {
            return tap(stream_get_contents($card), fn () => fclose($card));
        }

        return $card;
    }
}
