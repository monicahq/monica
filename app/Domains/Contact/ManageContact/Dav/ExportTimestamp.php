<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(1000)]
#[VCardType(Contact::class)]
class ExportTimestamp implements ExportVCardResource
{
    /**
     * @param  Contact  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('REV');

        $vcard->REV = $resource->updated_at->format('Ymd\\THis\\Z');
    }
}
