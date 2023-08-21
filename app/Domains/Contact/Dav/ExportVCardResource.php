<?php

namespace App\Domains\Contact\Dav;

use Sabre\VObject\Component\VCard;

/**
 * @template T of \App\Domains\Contact\Dav\VCardResource
 */
interface ExportVCardResource
{
    /**
     * @return class-string<T>
     */
    public function getType(): string;

    /**
     * @param  T  $resource
     */
    public function export(mixed $resource, VCard $vcard): void;
}
