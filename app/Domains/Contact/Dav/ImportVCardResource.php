<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

interface ImportVCardResource
{
    /**
     * Set context.
     */
    public function setContext(ImportVCard $context): self;

    /**
     * Import Contact.
     */
    public function import(?Contact $contact, VCard $vcard): Contact;
}
