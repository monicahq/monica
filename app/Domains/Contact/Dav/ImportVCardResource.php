<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

interface ImportVCardResource
{
    /**
     * Set context.
     *
     * @param  ImportVCard  $context
     * @return self
     */
    public function setContext(ImportVCard $context): self;

    /**
     * Import Contact.
     *
     * @param  Contact|null  $contact
     * @param  VCard  $vcard
     * @return Contact
     */
    public function import(?Contact $contact, VCard $vcard): Contact;
}
