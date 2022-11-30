<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

#[Order(1000)]
class ExportTimestamp implements ExportVCardResource
{
    /**
     * @param  Contact  $contact
     * @param  VCard  $vcard
     */
    public function export(Contact $contact, VCard $vcard): void
    {
        $vcard->remove('REV');

        $vcard->REV = $contact->updated_at->format('Ymd\\THis\\Z');
    }
}
