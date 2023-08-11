<?php

namespace App\Domains\Contact\Dav;

use Sabre\VObject\Component\VCard;

/**
 * @template T of VCardResource
 */
interface ExportVCardResource
{
    /**
     * @param  T  $resource
     */
    public function export($resource, VCard $vcard): void;
}
