<?php

namespace App\Domains\Contact\Dav;

use App\Models\Contact;
use Sabre\VObject\Component\VCard;

interface ExportVCardResource
{
    public function export(Contact $contact, VCard $vcard): void;
}
