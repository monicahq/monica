<?php

namespace App\Domains\Contact\Dav;

use Sabre\VObject\Component\VCard;

interface ExportVCardResource
{
    public function export(VCardResource $resource, VCard $vcard): void;
}
