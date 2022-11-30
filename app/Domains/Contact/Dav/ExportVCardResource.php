<?php

namespace App\Domains\Contact\Dav;

use App\Models\Contact;
use Sabre\VObject\Component\VCard;

interface ExportVCardResource
{
    /**
     * @param  Contact  $contact
     * @param  VCard  $vcard
     */
    public function export(Contact $contact, VCard $vcard): void;
}
