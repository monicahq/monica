<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use Sabre\VObject\Component\VCard;

interface ImportVCardResource
{
    /**
     * Set context.
     */
    public function setContext(ImportVCard $context): self;

    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool;

    /**
     * Can import Card.
     */
    public function can(VCard $vcard): bool;

    /**
     * Import Card.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource;
}
